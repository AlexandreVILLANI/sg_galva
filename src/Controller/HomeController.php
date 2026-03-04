<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

use App\Repository\FicheDechargementRepository;
use App\Repository\BonDeCommandeRepository; 
use App\Repository\BonTravailRepository;
use App\Repository\PlanningRepository; 

class HomeController extends AbstractController
{
    /**
     * Route racine "/" : L'aiguilleur central
     */
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');
        
        if ($this->isGranted('ROLE_ADMIN')) return $this->redirectToRoute('app_admin_home');
        if ($this->isGranted('ROLE_CHEF_EQUIPE')) return $this->redirectToRoute('app_chef_equipe_home');
        if ($this->isGranted('ROLE_ORDONNANCEMENT')) return $this->redirectToRoute('app_ordonnancement_home');
        if ($this->isGranted('ROLE_RECEPTION_ORDONNANCEMENT')) return $this->redirectToRoute('app_reception_ordonnancement_home');
        if ($this->isGranted('ROLE_RECEPTION_TERRAIN')) return $this->redirectToRoute('app_reception_terrain_home');
        
        return $this->redirectToRoute('app_home');
    }

    /**
     * Espace CARISTE (ROLE_USER / ROLE_CARISTE)
     */
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function home(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_home');
        }
        
        // --- NOUVEAU : Sécurité pour Ordonnancement ---
        if ($this->isGranted('ROLE_ORDONNANCEMENT')) {
            return $this->redirectToRoute('app_ordonnancement_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION_ORDONNANCEMENT')) {
            return $this->redirectToRoute('app_reception_ordonnancement_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION_TERRAIN')) {
            return $this->redirectToRoute('app_reception_terrain_home');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'Espace Cariste',
        ]);
    }

    /**
     * Espace RÉCEPTION TERRAIN (Thibaut)
     */
    #[Route('/reception-terrain', name: 'app_reception_terrain_home')]
    #[IsGranted('ROLE_RECEPTION_TERRAIN')]
    public function receptionIndex(
        Request $request, 
        FicheDechargementRepository $ficheRepository,
        BonDeCommandeRepository $bcRepository 
    ): Response {
        $client = $request->query->get('client');
        $cariste = $request->query->get('cariste');
        $date = $request->query->get('date');
        $section = $request->query->get('section');

        $fiches = $ficheRepository->findWithFilters($client, $cariste, $date);
        $bons = $bcRepository->findBy([], ['date' => 'DESC'], 10);

        $queryForfaits = $bcRepository->createQueryBuilder('b')
            ->select('DISTINCT b.forfait AS name')
            ->where('b.forfait IS NOT NULL')
            ->orderBy('b.forfait', 'ASC')
            ->getQuery()
            ->getScalarResult();
        
        $uniqueForfaits = array_filter(array_column($queryForfaits, 'name'));

        $queryCaristes = $ficheRepository->createQueryBuilder('f')
            ->select('DISTINCT u.prenom AS name')
            ->join('f.cariste', 'u')
            ->orderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getScalarResult();
            
        $uniqueCaristes = array_filter(array_column($queryCaristes, 'name'));

        return $this->render('home/reception_terrain.html.twig', [
            'fiches' => $fiches,
            'bons' => $bons,
            'uniqueForfaits' => $uniqueForfaits,
            'uniqueCaristes' => $uniqueCaristes,
            'search_client' => $client,
            'search_cariste' => $cariste,
            'search_date' => $date,
            'active_section' => $section, 
        ]);
    }

    /**
     * Espace RÉCEPTION ORDONNANCEMENT (Dali)
     */
    #[Route('/reception-ordonnancement', name: 'app_reception_ordonnancement_home')]
    #[IsGranted('ROLE_RECEPTION_ORDONNANCEMENT')]
    public function receptionOrdonnancementIndex(
        BonDeCommandeRepository $bcRepository,
        BonTravailRepository $btRepository
    ): Response {
        $bons = $bcRepository->findBy([], ['date' => 'DESC']);
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('home/reception_ordonnancement.html.twig', [
            'bons' => $bons,
            'bons_travail' => $bons_travail,
        ]);
    }

    /**
     * Espace CHEF D'ÉQUIPE
     */
    #[Route('/chef-equipe', name: 'app_chef_equipe_home')]
    #[IsGranted('ROLE_CHEF_EQUIPE')]
    public function chefEquipeIndex(PlanningRepository $planningRepository): Response
    {
        // On récupère les plannings récents (ceux d'aujourd'hui et les derniers créés)
        // pour qu'il puisse pointer l'avancement.
        $plannings = $planningRepository->findBy([], ['datePlanning' => 'DESC'], 10);

        return $this->render('home/chef_equipe.html.twig', [
            'plannings' => $plannings,
        ]);
    }
    
    /**
     * Espace ORDONNANCEMENT (Gestion des Plannings GB/PB)
     */
    #[Route('/ordonnancement', name: 'app_ordonnancement_home')]
    #[IsGranted('ROLE_ORDONNANCEMENT')]
    public function ordonnancementIndex(
        BonTravailRepository $btRepository,
        PlanningRepository $planningRepository // <--- INJECTION ICI
    ): Response {
        
        // 1. On récupère les Bons de Travail pour l'onglet "À planifier"
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC']);
        
        // 2. On récupère les Plannings pour l'onglet "Plannings du Jour"
        $plannings = $planningRepository->findBy([], ['datePlanning' => 'DESC']);
        
        return $this->render('home/ordonnancement.html.twig', [
            'bons_travail' => $bons_travail,
            'plannings' => $plannings,
        ]);
    }

    /**
     * Espace ADMIN
     */
    #[Route('/admin', name: 'app_admin_home')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('home/admin.html.twig', [
            'controller_name' => 'Tableau de bord Administrateur',
        ]);
    }
}