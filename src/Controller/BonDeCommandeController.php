<?php

namespace App\Controller;

use App\Entity\BonDeCommande;
use App\Form\BonDeCommandeType;
use App\Repository\BonDeCommandeRepository;
use App\Repository\FicheDechargementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\PhotoBonCommande; 

class BonDeCommandeController extends AbstractController
{
    #[Route('/reception/bon-commande/creer/{ficheId}', name: 'app_bon_commande_new')]
    public function new(
        int $ficheId, 
        FicheDechargementRepository $ficheRepo, 
        BonDeCommandeRepository $bcRepo, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        
        $fiche = $ficheRepo->find($ficheId);
        if (!$fiche) {
            throw $this->createNotFoundException('Fiche de déchargement introuvable.');
        }

        $bon = new BonDeCommande();
        $bon->setFiche($fiche);
        $bon->setClient($fiche->getClient());
        $bon->setDate(new \DateTime());

        // --- LOGIQUE DU REFI SÉQUENTIEL À 6 CHIFFRES ---
        $lastRefi = $bcRepo->getLastRefi(); 
        
        if (!$lastRefi) {
            // Si c'est le tout premier bon, on commence à 1 (ou 000001)
            $nextNumber = 1; 
        } else {
            // On extrait uniquement les chiffres du dernier REFI (ex: "REFI-2026-0015" -> 15)
            // On cherche le dernier groupe de chiffres à la fin de la chaîne
            preg_match_all('/\d+/', $lastRefi, $matches);
            $numbers = $matches[0];
            
            // On prend le dernier nombre trouvé (ex: si REFI-20260204-111, on prend 111)
            $lastNumber = (int) end($numbers);
            $nextNumber = $lastNumber + 1;
        }

        // Formatage final : 6 chiffres (ex: 000001, 464932)
        // On ne met PLUS le préfixe "REFI-" comme demandé
        $formattedRefi = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        $bon->setRefi($formattedRefi);

        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move($this->getParameter('kernel.project_dir').'/public/uploads/bons', $newFilename);
                    
                    $photo = new PhotoBonCommande();
                    $photo->setNomFichier($newFilename);
                    $bon->addPhoto($photo);
                }
            }
            
            $em->persist($bon);
            $em->flush();

            $this->addFlash('success', "Le Bon de Commande n°$formattedRefi a été créé !");
            return $this->redirectToRoute('app_reception_home', ['section' => 'list-dechargement']);
        }

        return $this->render('bon_commande/new.html.twig', [
            'form' => $form->createView(),
            'fiche' => $fiche
        ]);
    }

    #[Route('/reception/bon-commande/voir/{id}', name: 'app_bon_commande_show')]
    public function show(BonDeCommande $bon): Response
    {
        return $this->render('bon_commande/show.html.twig', [
            'bon' => $bon,
            'fiche' => $bon->getFiche(), 
        ]);
    }
}