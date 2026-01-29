<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    /**
     * Route racine "/" : L'aiguilleur central
     */
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Priorité des redirections selon le rôle le plus haut
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION')) {
            return $this->redirectToRoute('app_reception_home');
        }

        if ($this->isGranted('ROLE_CARISTE')) {
            return $this->redirectToRoute('app_home'); // Les caristes vont sur /home
        }
        
        return $this->redirectToRoute('app_home');
    }

    /**
     * Espace CARISTE (ROLE_USER / ROLE_CARISTE)
     */
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function home(): Response
    {
        // Si un Admin ou une Réception arrive ici, on les redirige vers leur dashboard propre
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_home');
        }
        if ($this->isGranted('ROLE_RECEPTION')) {
            return $this->redirectToRoute('app_reception_home');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'Espace Cariste',
        ]);
    }

    /**
     * Espace RÉCEPTION
     */
    #[Route('/reception', name: 'app_reception_home')]
    #[IsGranted('ROLE_RECEPTION')]
    public function receptionIndex(): Response
    {
        return $this->render('home/reception.html.twig', [
            'controller_name' => 'Tableau de bord Réception',
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