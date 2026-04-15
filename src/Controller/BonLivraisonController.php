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
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
            return $this->redirectToRoute('app_livraison_show', ['id' => $existingBl->getId()]);
        }

        // 2. CRÉATION du nouveau BL
        $bl = new BonLivraison();
        $bl->setBonTravail($bt);

        // 3. GÉNÉRATION DU NUMÉRO UNIQUE (Ex: BL-24-0001)
        $currentYear = date('y'); 
        $lastBl = $blRepo->findOneBy([], ['id' => 'DESC']); 
        
        $nextSequence = 1;
        if ($lastBl && $lastBl->getNumero()) {
            $parts = explode('-', $lastBl->getNumero());
            $lastYear = $parts[1] ?? '';
            $lastSequence = (int) end($parts); 

            if ($lastYear === $currentYear) {
                $nextSequence = $lastSequence + 1;
            }
        }
        
        $newNumero = sprintf('BL-%s-%s', $currentYear, str_pad($nextSequence, 4, '0', STR_PAD_LEFT));
        $bl->setNumero($newNumero);

        // 4. SAUVEGARDE EN BASE
        $em->persist($bl);
        $em->flush();

        $this->addFlash('success', 'Bon de Livraison ' . $newNumero . ' généré avec succès !');

        return $this->redirectToRoute('app_livraison_show', ['id' => $bl->getId()]);
    }

    /**
     * ROUTE 2 : Voir et Compléter le Bon de Livraison (Vue classique / Bureau)
     */
    #[Route('/bon-livraison/{id}', name: 'app_livraison_show', methods: ['GET', 'POST'])]
    public function show(BonLivraison $bl, Request $request, EntityManagerInterface $em): Response
    {
        $bt = $bl->getBonTravail();
        $commande = $bt->getBonCommande();

        $form = $this->createForm(BonLivraisonType::class, $bl);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // On vérifie si une signature a été déposée
            if ($bl->getSignature() !== null && $bl->getSignature() !== '') {
                $bl->setSignatureValide(true);
            } else {
                $bl->setSignatureValide(false);
            }

            $em->flush();

            $this->addFlash('success', 'Informations de livraison et signature enregistrées !');

            return $this->redirectToRoute('app_livraison_show', ['id' => $bl->getId()]);
        }

        return $this->render('bon_livraison/show.html.twig', [
            'bl' => $bl,
            'bt' => $bt,
            'commande' => $commande,
            'form' => $form->createView(), 
        ]);
    }

    /**
     * ROUTE 3 : L'INTERFACE SPÉCIFIQUE CARISTE (Avec auto-validation simplifiée)
     */
    #[Route('/livraison/{id}/cariste', name: 'app_livraison_cariste_show', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function showForCariste(BonLivraison $bl, Request $request, EntityManagerInterface $em): Response
    {
        $bt = $bl->getBonTravail();
        $commande = $bt ? $bt->getBonCommande() : null;

        // Si on reçoit une action POST (le clic sur le bouton)
        if ($request->isMethod('POST')) {
            
            // On récupère le cariste connecté
            $cariste = $this->getUser();
            
            // On force la modification de l'entité
            $bl->setCariste($cariste);
            $bl->setCaristeValide(true);
            
            // ON FORCE DOCTRINE À PRENDRE EN COMPTE L'OBJET
            $em->persist($bl); 
            $em->flush();

            $this->addFlash('success', 'Le chargement a bien été validé par vos soins !');
            
            // Redirection vers le tableau de bord du cariste
            return $this->redirectToRoute('app_home');
        }

        // On envoie la vue SANS le form Symfony
        return $this->render('bon_livraison/cariste.html.twig', [
            'bl' => $bl,
            'bt' => $bt,
            'commande' => $commande,
        ]);
    }

    /**
     * ROUTE 4 : Supprimer un Bon de Livraison
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

        return $this->redirectToRoute('app_reception_ordonnancement_home', ['section' => 'list-bons-livraison']);
    }
}