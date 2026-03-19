<?php

namespace App\Controller;

use App\Repository\BonDeCommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/colisage')]
#[IsGranted('ROLE_COLISAGE')]
class ColisageController extends AbstractController
{
    #[Route('/', name: 'app_colisage_home')]
    public function index(Request $request, BonDeCommandeRepository $bcRepo): Response
    {
        // On récupère le REFI tapé dans la barre de recherche
        $refiRecherche = $request->query->get('refi');
        
        $commande = null;
        $erreur = null;

        if ($refiRecherche) {
            // On nettoie les espaces éventuels
            $refiRecherche = trim($refiRecherche);
            $commande = $bcRepo->findOneByRefiWithPhotos($refiRecherche);

            if (!$commande) {
                $erreur = "Aucune commande trouvée pour le REFI : " . $refiRecherche;
            }
        }

        // --- NOUVEAU : On récupère toutes les commandes pour la liste déroulante ---
        // On les trie par ID décroissant pour avoir les plus récentes en haut
        $listeCommandes = $bcRepo->findBy([], ['id' => 'DESC']);

        return $this->render('colisage/index.html.twig', [
            'commande' => $commande,
            'refi_recherche' => $refiRecherche,
            'erreur' => $erreur,
            // --- NOUVEAU : On envoie la liste au template ---
            'liste_commandes' => $listeCommandes, 
        ]);
    }
}