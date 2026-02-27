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

    /**
     * SUPPRIMER UN PLANNING (Action sécurisée)
     */
    #[Route('/ordonnancement/planning/{id}/delete', name: 'app_planning_delete', methods: ['POST'])]
    public function delete(Request $request, Planning $planning, EntityManagerInterface $em): Response
    {
        // On vérifie le jeton CSRF pour éviter les suppressions accidentelles ou malveillantes
        if ($this->isCsrfTokenValid('delete'.$planning->getId(), $request->request->get('_token'))) {
            $em->remove($planning);
            $em->flush();

            $this->addFlash('success', 'Le planning a été supprimé.');
        }

        // Redirection vers la page de liste (À ADAPTER SELON VOTRE ROUTE D'ACCUEIL)
        // Si votre route de liste s'appelle différemment, changez le nom ici :
        return $this->redirectToRoute('app_ordonnancement_home'); 
    }

    #[Route('/api/bt-info/{refi}', name: 'api_bt_info')]
    public function getBtInfo(string $refi, BonTravailRepository $btRepo): JsonResponse
    {
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
                ->setParameter('refi', $ligneData['refi'])
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
}