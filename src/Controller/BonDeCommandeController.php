<?php

namespace App\Controller;

use App\Entity\BonDeCommande;
use App\Entity\FicheDechargement;
use App\Form\BonDeCommandeType;
use App\Repository\FicheDechargementRepository; // Indispensable pour l'injection
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BonDeCommandeController extends AbstractController
{
    #[Route('/reception/bon-commande/creer/{ficheId}', name: 'app_bon_commande_new')]
    public function new(
        int $ficheId, 
        FicheDechargementRepository $ficheRepo, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        // 1. On récupère la fiche source
        $fiche = $ficheRepo->find($ficheId);
        
        // Sécurité : Si la fiche n'existe pas, on redirige avec une erreur
        if (!$fiche) {
            $this->addFlash('error', 'La fiche de déchargement demandée n\'existe pas.');
            return $this->redirectToRoute('app_reception_home');
        }

        // 2. On crée le nouveau Bon de Commande (REFI)
        $bon = new BonDeCommande();
        
        // --- TRANSFERT AUTOMATIQUE DES INFOS ---
        // Ces données seront "injectées" dans le formulaire et le template Twig
        $bon->setFiche($fiche);               
        $bon->setClient($fiche->getClient()); // Récupère automatiquement l'entité Client liée
        $bon->setDate(new \DateTime());       
        
        // Pré-remplissage du REFI (modifiable dans le formulaire)
        $bon->setRefi('REFI-' . date('Y') . '-' . rand(100, 999)); 

        // 3. Gestion du formulaire
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($bon);
            $em->flush();

            $this->addFlash('success', 'Le Bon de Commande (REFI) a été créé avec succès.');
            return $this->redirectToRoute('app_reception_home');
        }

        // 4. On passe 'form' et 'fiche' au template
        // 'fiche' permet d'afficher les paquets, emplacements et photos sans les stocker 2 fois
        return $this->render('bon_commande/new.html.twig', [
            'form' => $form->createView(),
            'fiche' => $fiche
        ]);
    }
}