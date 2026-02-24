<?php

namespace App\Controller;

use App\Repository\BonTravailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\BonTravail;
use Symfony\Component\HttpFoundation\JsonResponse;

class PlanningController extends AbstractController
{
    #[Route('/ordonnancement/planning/nouveau', name: 'app_planning_new')]
    public function new(BonTravailRepository $btRepository): Response
    {
        // On récupère quelques BT pour le test
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
    public function searchBt(\Symfony\Component\HttpFoundation\Request $request, \App\Repository\BonTravailRepository $btRepo): \Symfony\Component\HttpFoundation\JsonResponse
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

        return new \Symfony\Component\HttpFoundation\JsonResponse($data);
    }
}