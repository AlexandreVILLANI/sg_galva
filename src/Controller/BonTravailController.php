<?php

namespace App\Controller;

use App\Entity\BonDeCommande;
use App\Entity\BonTravail;
use App\Entity\LigneDechargement;

use App\Form\BonTravailType;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Repository\BonTravailRepository; 


class BonTravailController extends AbstractController
{
    /**
     * ROUTE 1 : Création / Édition du Bon de Travail (Pour Dali)
     */
    #[Route('/bon-travail/generer/{id}', name: 'app_bon_travail_new')]
    public function new(BonDeCommande $commande, Request $request, EntityManagerInterface $em, BonTravailRepository $btRepo): Response 
    {
        $bt = $commande->getBonTravail();

        if (!$bt) {
            $bt = new BonTravail();
            $bt->setBonCommande($commande);

            $lastNumero = $btRepo->findLastNumero();
            $currentYear = date('y'); 
            $nextSequence = 1;

            if ($lastNumero) {
                $parts = explode('-', $lastNumero);
                $lastYear = $parts[1] ?? '';
                $lastSequence = (int) end($parts); 

                if ($lastYear === $currentYear) {
                    $nextSequence = $lastSequence + 1;
                }
            }

            $newNumero = sprintf('BT-%s-%s', $currentYear, str_pad($nextSequence, 4, '0', STR_PAD_LEFT));
            
            $bt->setNumero($newNumero);
            
            $em->persist($bt);
            $em->flush();
        }

        $form = $this->createForm(BonTravailType::class, $bt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Bon de travail mis à jour !');
            return $this->redirectToRoute('app_reception_ordonnancement_home');
        }

        return $this->render('bon_travail/new.html.twig', [
            'form' => $form->createView(),
            'bt' => $bt,
            'commande' => $commande,
            'lignes' => $bt->getLignes(), 
        ]);
    }

    /**
     * ROUTE 2 : Vue en LECTURE SEULE (sauf le délai) pour Ordonnancement
     */
    #[Route('/bon-travail/voir/{id}', name: 'app_bon_travail_show', methods: ['GET', 'POST'])]
    public function show(BonTravail $bt, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $nouveauDelai = $request->request->get('delai_client');
            
            if ($nouveauDelai) {
                $bt->setDelaiClient(new \DateTime($nouveauDelai));
            } else {
                $bt->setDelaiClient(null); 
            }

            $em->flush();
            $this->addFlash('success', 'Le délai client a été mis à jour.');
        
            return $this->redirectToRoute('app_bon_travail_show', ['id' => $bt->getId()]);
        }

        return $this->render('bon_travail/show.html.twig', [
            'bt' => $bt,
            'commande' => $bt->getBonCommande(),
        ]);
    }

    /**
     * ROUTE 3 : Vue 100% LECTURE SEULE pour le Chef d'Équipe (Atelier)
     */
    #[Route('/bon-travail/consulter/{id}', name: 'app_bon_travail_view', methods: ['GET'])]
    public function view(BonTravail $bt): Response
    {
        // On n'autorise aucune modification ici, c'est juste de l'affichage
        return $this->render('bon_travail/view.html.twig', [
            'bt' => $bt,
            'commande' => $bt->getBonCommande(),
        ]);
    }

    #[Route('/reception-ordonnancement/bon-travail/supprimer/{id}', name: 'app_bon_travail_delete', methods: ['POST'])]
    #[IsGranted('ROLE_RECEPTION_ORDONNANCEMENT')]
    public function delete(Request $request, BonTravail $bt, EntityManagerInterface $em): Response
    {
        $tokenId = 'delete_bt' . $bt->getId();
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid($tokenId, $submittedToken)) {
            $em->remove($bt);
            $em->flush();
            $this->addFlash('success', 'BT supprimé.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_reception_ordonnancement_home', ['section' => 'list-bons-travail']);
    }
}