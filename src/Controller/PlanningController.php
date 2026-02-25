<?php

namespace App\Controller;

use App\Entity\BonTravail;
use App\Entity\Planning;
use App\Entity\PlanningLigne;
use App\Repository\BonTravailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanningController extends AbstractController
{

    /**
     * AFFICHER LA PAGE DE MODIFICATION D'UN PLANNING EXISTANT
     */
    #[Route('/ordonnancement/planning/{id}/modifier', name: 'app_planning_edit')]
    public function edit(Planning $planning): Response
    {
        return $this->render('planning/edit.html.twig', [
            'planning' => $planning,
        ]);
    }

    /**
     * SAUVEGARDER LES MODIFICATIONS D'UN PLANNING EXISTANT
     */
    #[Route('/api/planning/{id}/update', name: 'app_planning_update', methods: ['POST'])]
    public function updatePlanning(Planning $planning, Request $request, EntityManagerInterface $em, BonTravailRepository $btRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['lignes'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $planning->setCategorie($data['categorie'] ?? 'GB');

        // On garde une trace des lignes existantes pour savoir lesquelles supprimer si Gérard a cliqué sur la croix
        $existingLignes = $planning->getLignes();
        $receivedIds = [];

        foreach ($data['lignes'] as $ligneData) {
            $bt = $btRepo->createQueryBuilder('bt')
                ->join('bt.bonCommande', 'bc')
                ->where('bc.refi = :refi')
                ->setParameter('refi', $ligneData['refi'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($bt) {
                // Si la ligne a un ID, on la modifie, sinon on la crée
                if (!empty($ligneData['id'])) {
                    $ligne = $em->getRepository(PlanningLigne::class)->find($ligneData['id']);
                    $receivedIds[] = $ligne->getId();
                } else {
                    $ligne = new PlanningLigne();
                    $ligne->setPlanning($planning);
                    $ligne->setAvancement(false); // Fait = false par défaut pour les nouvelles lignes
                    $em->persist($ligne);
                }

                $ligne->setBonTravail($bt);
                
                if (!empty($ligneData['hmad'])) {
                    try { $ligne->setHeureMiseADisposition(new \DateTime($ligneData['hmad'])); } 
                    catch (\Exception $e) {}
                }
                
                $ligne->setImportance($ligneData['important'] ?? false);
                $ligne->setObservations($ligneData['observations'] ?? null);
                $ligne->setAvancementCode($ligneData['code'] ?? null);
            }
        }

        // On supprime les lignes que Gérard a enlevées (uniquement si elles ne sont pas déjà cochées "Faites" par le chef)
        foreach ($existingLignes as $exLigne) {
            if (!in_array($exLigne->getId(), $receivedIds) && !$exLigne->isAvancement()) {
                $em->remove($exLigne);
            }
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    
    #[Route('/ordonnancement/planning/nouveau', name: 'app_planning_new')]
    public function new(BonTravailRepository $btRepository): Response
    {
        // On récupère quelques BT pour le test (si besoin de pré-remplir)
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC'], 10);

        return $this->render('planning/new.html.twig', [
            'bons_travail' => $bons_travail,
        ]);
    }

    #[Route('/api/bt-info/{refi}', name: 'api_bt_info')]
    public function getBtInfo(string $refi, BonTravailRepository $btRepo): JsonResponse
    {
        // On cherche le Bon de Travail par le REFI du Bon de Commande associé
        $bt = $btRepo->createQueryBuilder('bt')
            ->join('bt.bonCommande', 'bc')
            ->where('bc.refi = :refi')
            ->setParameter('refi', $refi)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$bt) {
            return new JsonResponse(['error' => 'Non trouvé'], 404);
        }

        return new JsonResponse([
            'client' => $bt->getBonCommande()->getClient()->getNom(),
            'bt_id' => $bt->getId()
        ]);
    }

    #[Route('/api/search-bt', name: 'api_search_bt')]
    public function searchBt(Request $request, BonTravailRepository $btRepo): JsonResponse
    {
        $term = $request->query->get('q', '');
        
        $results = $btRepo->createQueryBuilder('bt')
            ->join('bt.bonCommande', 'bc')
            ->join('bc.client', 'c')
            ->where('bc.refi LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($results as $bt) {
            $data[] = [
                'refi' => $bt->getBonCommande()->getRefi(),
                'client' => $bt->getBonCommande()->getClient()->getNom()
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * NOUVELLE ROUTE : SAUVEGARDE DU PLANNING DANS LA BASE DE DONNÉES
     */
    #[Route('/api/planning/save', name: 'app_planning_save', methods: ['POST'])]
    public function savePlanning(Request $request, EntityManagerInterface $em, BonTravailRepository $btRepo): JsonResponse
    {
        // 1. On récupère le JSON envoyé par le Javascript (fetch)
        $data = json_decode($request->getContent(), true);

        // Vérification de sécurité
        if (!$data || !isset($data['lignes']) || empty($data['lignes'])) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune donnée reçue ou tableau vide.'], 400);
        }

        // 2. Création de l'entête du Planning
        $planning = new Planning();
        $planning->setDatePlanning(new \DateTime()); // Date de création (aujourd'hui)
        $planning->setCategorie($data['categorie'] ?? 'GB'); // GB ou PB

        // On "prépare" la sauvegarde du planning
        $em->persist($planning);

        // 3. Boucle sur toutes les lignes envoyées par le tableau
        foreach ($data['lignes'] as $ligneData) {
            
            // On retrouve le Bon de Travail grâce au REFI
            $bt = $btRepo->createQueryBuilder('bt')
                ->join('bt.bonCommande', 'bc')
                ->where('bc.refi = :refi')
                ->setParameter('refi', $ligneData['refi'])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Si le Bon de Travail existe bien, on crée la ligne de planning
            if ($bt) {
                $ligne = new PlanningLigne();
                $ligne->setPlanning($planning);
                $ligne->setBonTravail($bt);
                
                // Gestion de la date et heure (H.M.A.D)
                if (!empty($ligneData['hmad'])) {
                    try {
                        $ligne->setHeureMiseADisposition(new \DateTime($ligneData['hmad']));
                    } catch (\Exception $e) {
                        // En cas de format invalide, on ignore sans bloquer
                    }
                }
                
                $ligne->setImportance($ligneData['important'] ?? false);
                $ligne->setObservations($ligneData['observations'] ?? null);
                $ligne->setAvancementCode($ligneData['code'] ?? null);
                
                // Fait/Non fait (toujours à "false" quand Gérard crée le planning)
                $ligne->setAvancement(false);

                // On "prépare" la sauvegarde de cette ligne
                $em->persist($ligne);
            }
        }

        // 4. On exécute toutes les requêtes d'un coup (Sauvegarde finale)
        $em->flush();

        // 5. On renvoie un signal de succès au Javascript
        return new JsonResponse(['success' => true]);
    }
}