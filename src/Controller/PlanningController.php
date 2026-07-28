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
use Symfony\Component\Security\Http\Attribute\IsGranted; // <-- AJOUT IMPORTANT POUR LA SÉCURITÉ

class PlanningController extends AbstractController
{
    // =========================================================================
    // PARTIE ORDONNANCEMENT (Inchangée)
    // =========================================================================

    #[Route('/ordonnancement/planning/{id}/modifier', name: 'app_planning_edit')]
    public function edit(Planning $planning): Response
    {
        return $this->render('planning/edit.html.twig', [
            'planning' => $planning,
        ]);
    }

    #[Route('/api/planning/{id}/update', name: 'app_planning_update', methods: ['POST'])]
    public function updatePlanning(Planning $planning, Request $request, EntityManagerInterface $em, BonTravailRepository $btRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['lignes'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $planning->setCategorie($data['categorie'] ?? 'GB');

        $existingLignes = $planning->getLignes();
        $receivedIds = [];

        foreach ($data['lignes'] as $ligneData) {
            $bt = $btRepo->createQueryBuilder('bt')
                ->join('bt.bonCommande', 'bc')
                ->where('bc.refi = :refi')
                ->andWhere('bt.type = :type')
                ->setParameter('refi', $ligneData['refi'])
                ->setParameter('type', 'GALVA')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($bt) {
                if (!empty($ligneData['id'])) {
                    $ligne = $em->getRepository(PlanningLigne::class)->find($ligneData['id']);
                    $receivedIds[] = $ligne->getId();
                } else {
                    $ligne = new PlanningLigne();
                    $ligne->setPlanning($planning);
                    $ligne->setAvancement(false); 
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
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC'], 10);

        return $this->render('planning/new.html.twig', [
            'bons_travail' => $bons_travail,
        ]);
    }

    #[Route('/ordonnancement/planning/{id}/delete', name: 'app_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Planning $planning, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$planning->getId(), $request->request->get('_token'))) {
            $em->remove($planning);
            $em->flush();
            $this->addFlash('success', 'Le planning a été supprimé.');
        }
        return $this->redirectToRoute('app_ordonnancement_home'); 
    }

    #[Route('/api/bt-info/{refi}', name: 'api_bt_info')]
    public function getBtInfo(string $refi, BonTravailRepository $btRepo): JsonResponse
    {
        $bt = $btRepo->createQueryBuilder('bt')
            ->join('bt.bonCommande', 'bc')
            ->where('bc.refi = :refi')
            ->andWhere('bt.type = :type')
            ->setParameter('refi', $refi)
            ->setParameter('type', 'GALVA')
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
            ->andWhere('bt.type = :type')
            ->setParameter('term', '%' . $term . '%')
            ->setParameter('type', 'GALVA')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($results as $bt) {
            $data[] = ['refi' => $bt->getBonCommande()->getRefi(), 'client' => $bt->getBonCommande()->getClient()->getNom()];
        }
        return new JsonResponse($data);
    }

    #[Route('/api/planning/save', name: 'app_planning_save', methods: ['POST'])]
    public function savePlanning(Request $request, EntityManagerInterface $em, BonTravailRepository $btRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['lignes']) || empty($data['lignes'])) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune donnée reçue.'], 400);
        }

        $planning = new Planning();
        $planning->setDatePlanning(new \DateTime());
        $planning->setCategorie($data['categorie'] ?? 'GB');

        $em->persist($planning);

        foreach ($data['lignes'] as $ligneData) {
            $bt = $btRepo->createQueryBuilder('bt')
                ->join('bt.bonCommande', 'bc')
                ->where('bc.refi = :refi')
                ->andWhere('bt.type = :type')
                ->setParameter('refi', $ligneData['refi'])
                ->setParameter('type', 'GALVA')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($bt) {
                $ligne = new PlanningLigne();
                $ligne->setPlanning($planning);
                $ligne->setBonTravail($bt);
                
                if (!empty($ligneData['hmad'])) {
                    try { $ligne->setHeureMiseADisposition(new \DateTime($ligneData['hmad'])); } 
                    catch (\Exception $e) {}
                }
                
                $ligne->setImportance($ligneData['important'] ?? false);
                $ligne->setObservations($ligneData['observations'] ?? null);
                $ligne->setAvancementCode($ligneData['code'] ?? null);
                $ligne->setAvancement(false);

                $em->persist($ligne);
            }
        }

        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    // =========================================================================
    // PARTIE CHEF D'ÉQUIPE (Qualité & Validation)
    // =========================================================================

    /**
     * OUVRE LE PLANNING POUR LE CHEF D'ÉQUIPE (Fichier add.html.twig)
     */
    #[Route('/chef-equipe/planning/{id}/saisir', name: 'app_planning_chef_edit')]
    public function editQuality(Planning $planning): Response
    {
        return $this->render('planning/add.html.twig', [
            'planning' => $planning,
        ]);
    }

    /**
     * SAUVEGARDE UNIQUEMENT LES DONNÉES DE QUALITÉ DU CHEF D'ÉQUIPE
     */
    #[Route('/api/planning/{id}/update-quality', name: 'app_planning_quality_update', methods: ['POST'])]
    public function updateQuality(Planning $planning, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['lignes'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        foreach ($data['lignes'] as $ligneData) {
            if (!empty($ligneData['id'])) {
                $ligne = $em->getRepository(PlanningLigne::class)->find($ligneData['id']);
                
                if ($ligne && $ligne->getPlanning() === $planning) {
                    
                    if (array_key_exists('qualiteConforme', $ligneData)) $ligne->setQualiteConforme($ligneData['qualiteConforme']);
                    if (array_key_exists('qualiteFicheNC', $ligneData)) $ligne->setQualiteFicheNC($ligneData['qualiteFicheNC']);
                    if (array_key_exists('qualiteOperations', $ligneData)) $ligne->setQualiteOperations($ligneData['qualiteOperations']);

                    if (array_key_exists('affichageCaseCE', $ligneData)) $ligne->setAffichageCaseCE($ligneData['affichageCaseCE']);
                    if (array_key_exists('affichageCaseControleur', $ligneData)) $ligne->setAffichageCaseControleur($ligneData['affichageCaseControleur']);

                    if (array_key_exists('traitementSurfaceConforme', $ligneData)) $ligne->setTraitementSurfaceConforme($ligneData['traitementSurfaceConforme']);
                    if (array_key_exists('bainZincConforme', $ligneData)) $ligne->setBainZincConforme($ligneData['bainZincConforme']);
                    if (array_key_exists('rebuts', $ligneData)) $ligne->setRebuts($ligneData['rebuts']);

                    if (array_key_exists('finalConforme', $ligneData)) $ligne->setFinalConforme($ligneData['finalConforme']);
                    if (array_key_exists('finalFicheNC', $ligneData)) $ligne->setFinalFicheNC($ligneData['finalFicheNC']);

                    if (array_key_exists('avancement', $ligneData)) {
                        $isFait = $ligneData['avancement'];
                        
                        if ($isFait && !$ligne->isAvancement()) {
                            $ligne->setDateValidation(new \DateTime()); 
                            $ligne->setValidePar($this->getUser());     
                        } 
                        elseif (!$isFait && $ligne->isAvancement()) {
                            $ligne->setDateValidation(null);
                            $ligne->setValidePar(null);
                        }
                        
                        $ligne->setAvancement($isFait);
                    }
                }
            }
        }

        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    // =========================================================================
    // NOUVELLE PARTIE : ADMINISTRATEUR (Tout modifiable)
    // =========================================================================

    /**
     * OUVRE LE PLANNING COMPLET POUR L'ADMINISTRATEUR
     */
    #[Route('/admin/planning/{id}/modifier', name: 'app_admin_planning_edit', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function editAdmin(Planning $planning): Response
    {
        return $this->render('planning/admin.html.twig', [
            'planning' => $planning,
        ]);
    }
}