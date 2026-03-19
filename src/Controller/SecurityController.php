<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use App\Security\AppAuthenticator;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Method intercepted by logout key.');
    }

    #[Route('/login-auto/{token}', name: 'app_magic_login')]
    public function magicLogin(string $token, UserRepository $userRepo, Security $security): Response
    {
        $user = $userRepo->findOneBy(['token' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide.');
            return $this->redirectToRoute('app_login'); // Redirige plutôt vers login que home si échec
        }

        $security->login($user, AppAuthenticator::class, 'main'); 

        // --- NOUVEAU : REDIRECTION SELON LE RÔLE ---
        if (in_array('ROLE_COLISAGE', $user->getRoles())) {
            return $this->redirectToRoute('app_colisage_home');
        }

        // Par défaut (pour les caristes)
        return $this->redirectToRoute('app_fiche_dechargement');
    }
}