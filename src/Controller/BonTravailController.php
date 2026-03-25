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
            } elseif ($commande->isGalvanisation()) {
                $commande->setIsCataphorese(false);
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

            // On part du principe que le BT est complet, et on va vérifier s'il y a une erreur
            $estComplet = true;
            
            // Sécurité : S'il n'y a aucune ligne à peser, on ne peut pas valider
            if ($bt->getLignes()->isEmpty()) {
                $estComplet = false;
            }

            foreach ($bt->getLignes() as $ligne) {
                $id = $ligne->getId();
                
                // --- 1. ENREGISTREMENT ET VÉRIFICATION DU POIDS ---
                if (isset($poidsData[$id]) && trim($poidsData[$id]) !== '') {
                    // On remplace la virgule par un point pour que PHP comprenne bien le chiffre
                    $poidsNettoye = str_replace(',', '.', $poidsData[$id]);
                    $valeurPoids = (float) $poidsNettoye;
                    
                    $ligne->setPoids($valeurPoids);
                    
                    // Si le poids saisi est de 0 (ou négatif), alors ce n'est pas fini !
                    if ($valeurPoids <= 0) {
                        $estComplet = false;
                    }
                } else {
                    // Si la case est restée vide, ce n'est pas fini !
                    $estComplet = false;
                }
                
                // --- 2. ENREGISTREMENT DES OBSERVATIONS ---
                if (isset($obsData[$id])) {
                    $ligne->setObservations((string) $obsData[$id]);
                }
            }

            // --- 3. VALIDATION AUTOMATIQUE ---
            // Si $estComplet est resté à "true" (donc toutes les cases > 0), le BT est validé.
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
            
            // --- NOUVEAU : VÉRIFICATION AVANT SUPPRESSION ---
            // On vérifie si le Bon de Travail possède des lignes de planning
            if (!$bt->getPlanningLignes()->isEmpty()) {
                // S'il est dans le planning, on bloque et on avertit l'utilisateur
                $this->addFlash('error', 'Impossible de supprimer le BT-' . $bt->getNumero() . ' car il est déjà planifié. Veuillez d\'abord le retirer du planning.');
            } else {
                // S'il n'est pas dans le planning, on a le droit de le supprimer
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