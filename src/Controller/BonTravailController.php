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
use App\Repository\BonTravailRepository; 

class BonTravailController extends AbstractController
{
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
}