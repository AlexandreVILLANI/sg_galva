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

            if ($commande->isCataphorese()) {
                $commande->setIsGalvanisation(false);
                $bt->setType('CATA');
            } elseif ($commande->isGalvanisation()) {
                $commande->setIsCataphorese(false);
                $bt->setType('GALVA');
            }

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
        return $this->render('bon_travail/view.html.twig', [
            'bt' => $bt,
            'commande' => $bt->getBonCommande(),
        ]);
    }

    /**
     * ROUTE 4 : Saisie des POIDS et OBSERVATIONS pour l'équipe PESÉE
     */
    #[Route('/pesee/bt/{id}', name: 'app_pesee_bt_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PESEE')]
    public function peseeBtEdit(BonTravail $bt, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $poidsData = $request->request->all('poids'); 
            $obsData = $request->request->all('observations');

            $estComplet = true;
            
            if ($bt->getLignes()->isEmpty()) {
                $estComplet = false;
            }

            foreach ($bt->getLignes() as $ligne) {
                $id = $ligne->getId();
                
                if (isset($poidsData[$id]) && trim($poidsData[$id]) !== '') {
                    $poidsNettoye = str_replace(',', '.', $poidsData[$id]);
                    $valeurPoids = (float) $poidsNettoye;
                    
                    $ligne->setPoids($valeurPoids);
                    
                    if ($valeurPoids <= 0) {
                        $estComplet = false;
                    }
                } else {
                    $estComplet = false;
                }
                
                if (isset($obsData[$id])) {
                    $ligne->setObservations((string) $obsData[$id]);
                }
            }

            $bt->setIsPeseeValidee($estComplet);

            $em->flush(); 
            
            if ($estComplet) {
                $this->addFlash('success', 'Pesée terminée et validée avec succès !');
            } else {
                $this->addFlash('success', 'Brouillon de la pesée enregistré (il manque encore des poids).');
            }
            
            return $this->redirectToRoute('app_pesee_bt_edit', ['id' => $bt->getId()]);
        }

        return $this->render('bon_travail/pesee_bt_edit.html.twig', [
            'bt' => $bt,
            'commande' => $bt->getBonCommande(),
        ]);
    }

    #[Route('/commercial/bon-travail/{id}', name: 'app_commercial_bt_show', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_COMMERCIAL')]
    public function commercialShowBT(BonTravail $bt, Request $request, EntityManagerInterface $em): Response
    {
        // 1. On crée le formulaire pour pouvoir sauvegarder les prix
        $form = $this->createForm(BonTravailType::class, $bt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Les prix ont été enregistrés avec succès !');
            
            // On le redirige vers sa page d'accueil après sauvegarde
            return $this->redirectToRoute('app_commercial_home'); 
        }

        return $this->render('bon_travail/commercial_edit.html.twig', [
            'bt' => $bt,
            'commande' => $bt->getBonCommande(), // <--- L'erreur venait d'ici, il manquait cette variable !
            'form' => $form->createView(),       // <--- Et il manquait le formulaire !
        ]);
    }

    /**
     * ROUTE SUPPRESSION
     */
    #[Route('/reception-ordonnancement/bon-travail/supprimer/{id}', name: 'app_bon_travail_delete', methods: ['POST'])]
    #[IsGranted('ROLE_RECEPTION_ORDONNANCEMENT')]
    public function delete(Request $request, BonTravail $bt, EntityManagerInterface $em): Response
    {
        $tokenId = 'delete_bt' . $bt->getId();
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid($tokenId, $submittedToken)) {
            
            if (!$bt->getPlanningLignes()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer le BT-' . $bt->getNumero() . ' car il est déjà planifié. Veuillez d\'abord le retirer du planning.');
            } else {
                $em->remove($bt);
                $em->flush();
                $this->addFlash('success', 'Bon de Travail supprimé avec succès.');
            }
            
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_reception_ordonnancement_home', ['section' => 'list-bons-travail']);
    }
}