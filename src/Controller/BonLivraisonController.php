<?php

namespace App\Controller;

use App\Entity\BonLivraison;
use App\Entity\BonTravail;
use App\Form\BonLivraisonType; 
use App\Repository\BonLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BonLivraisonController extends AbstractController
{
    /**
     * ROUTE 1 : Créer un nouveau Bon de Livraison depuis un Bon de Travail
     */
    #[Route('/bon-livraison/creer/{id}', name: 'app_livraison_new')]
    public function new(BonTravail $bt, BonLivraisonRepository $blRepo, EntityManagerInterface $em): Response
    {
        // 1. SÉCURITÉ : On vérifie si un BL n'existe pas DÉJÀ pour ce BT
        $existingBl = $blRepo->findOneBy(['bonTravail' => $bt]);
        
        if ($existingBl) {
            $this->addFlash('info', 'Le Bon de Livraison de cette commande existe déjà.');
            // Il existe déjà ? On redirige vers sa page pour le voir/l'éditer
            return $this->redirectToRoute('app_livraison_show', ['id' => $existingBl->getId()]);
        }

        // 2. CRÉATION du nouveau BL
        $bl = new BonLivraison();
        $bl->setBonTravail($bt);

        // 3. GÉNÉRATION DU NUMÉRO UNIQUE (Ex: BL-24-0001)
        $currentYear = date('y'); // Année en cours sur 2 chiffres
        $lastBl = $blRepo->findOneBy([], ['id' => 'DESC']); 
        
        $nextSequence = 1;
        if ($lastBl && $lastBl->getNumero()) {
            $parts = explode('-', $lastBl->getNumero());
            $lastYear = $parts[1] ?? '';
            $lastSequence = (int) end($parts); 

            // Si le dernier BL a été créé cette année, on fait +1
            if ($lastYear === $currentYear) {
                $nextSequence = $lastSequence + 1;
            }
        }
        
        // On formate avec des zéros devant : BL-24-0001, BL-24-0002...
        $newNumero = sprintf('BL-%s-%s', $currentYear, str_pad($nextSequence, 4, '0', STR_PAD_LEFT));
        $bl->setNumero($newNumero);

        // 4. SAUVEGARDE EN BASE
        $em->persist($bl);
        $em->flush();

        $this->addFlash('success', 'Bon de Livraison ' . $newNumero . ' généré avec succès !');

        // 5. REDIRECTION vers la page de ce nouveau BL
        return $this->redirectToRoute('app_livraison_show', ['id' => $bl->getId()]);
    }

    /**
     * ROUTE 2 : Voir, Compléter (Transporteur/Plaque) et Signer le Bon de Livraison
     */
    #[Route('/bon-livraison/{id}', name: 'app_livraison_show', methods: ['GET', 'POST'])]
    public function show(BonLivraison $bl, Request $request, EntityManagerInterface $em): Response
    {
        // On récupère le BT et la Commande associés
        $bt = $bl->getBonTravail();
        $commande = $bt->getBonCommande();

        // 1. Création et traitement du formulaire
        $form = $this->createForm(BonLivraisonType::class, $bl);
        $form->handleRequest($request);

        // 2. Si le formulaire est validé (Transporteur saisi, signature faite...)
        if ($form->isSubmitted() && $form->isValid()) {
            
            // On enregistre les modifications en base de données
            $em->flush();

            $this->addFlash('success', 'Informations de livraison et signature enregistrées !');

            // On recharge la page pour voir les données mises à jour
            return $this->redirectToRoute('app_livraison_show', ['id' => $bl->getId()]);
        }

        // 3. Affichage de la page (ON POINTE VERS show.html.twig MAINTENANT)
        return $this->render('bon_livraison/show.html.twig', [
            'bl' => $bl,
            'bt' => $bt,
            'commande' => $commande,
            'form' => $form->createView(), 
        ]);
    }

    /**
     * ROUTE 3 : Supprimer un Bon de Livraison
     */
    #[Route('/bon-livraison/supprimer/{id}', name: 'app_livraison_delete', methods: ['POST'])]
    public function delete(Request $request, BonLivraison $bl, EntityManagerInterface $em): Response
    {
        $tokenId = 'delete_bl' . $bl->getId();
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid($tokenId, $submittedToken)) {
            $em->remove($bl);
            $em->flush();
            $this->addFlash('success', 'Le Bon de Livraison a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        // On redirige vers le tableau de bord, onglet Bon de Livraison
        return $this->redirectToRoute('app_reception_ordonnancement_home', ['section' => 'list-bons-livraison']);
    }
}