<?php

namespace App\Controller;

use App\Entity\BonDeCommande;
use App\Form\BonDeCommandeType;
use App\Repository\BonDeCommandeRepository;
use App\Repository\LigneDechargementRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * PAGE D'ACCUEIL ADMIN (Liste tout)
     */
    #[Route('/', name: 'app_admin_home')]
    public function index(BonDeCommandeRepository $bcRepo): Response
    {
        // On récupère tous les bons de commande, triés par date décroissante
        $bons = $bcRepo->findBy([], ['date' => 'DESC']);

        return $this->render('home/admin.html.twig', [
            'bons' => $bons,
        ]);
    }

    #[Route('/bon-commande/{id}/edit', name: 'app_admin_bc_edit', methods: ['GET', 'POST'])]
    public function editBonCommande(
        Request $request, 
        BonDeCommande $bon, 
        EntityManagerInterface $em,
        LigneDechargementRepository $ligneRepo,
        ClientRepository $clientRepo // <-- Ajout de l'injection ici
    ): Response {
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- GESTION DU CHANGEMENT DE CLIENT ---
            $nouveauClientId = $request->request->get('nouveau_client');
            if ($nouveauClientId) {
                $nouveauClient = $clientRepo->find($nouveauClientId);
                if ($nouveauClient) {
                    $bon->getFiche()->setClient($nouveauClient);
                }
            }

            // --- SAUVEGARDE DES LIGNES DU TABLEAU ---
            $lignesData = $request->request->all('lignes');
            if (!empty($lignesData)) {
                $nouveauTotal = 0;
                foreach ($lignesData as $ligneId => $data) {
                    $ligne = $ligneRepo->find($ligneId);
                    if ($ligne) {
                        $ligne->setNbPaquets((int) $data['qte']);
                        $ligne->setDescription($data['desc']);
                        $nouveauTotal += (int) $data['qte'];
                    }
                }
                $bon->getFiche()->setTotalPaquets($nouveauTotal);
            }

            $em->flush();
            $this->addFlash('success', 'Bon de commande mis à jour avec succès.');
            
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-bc']);
        }

        return $this->render('bon_commande/admin.html.twig', [
            'form' => $form->createView(),
            'bon' => $bon,
            'fiche' => $bon->getFiche(),
            'clients' => $clientRepo->findBy([], ['nom' => 'ASC']), // <-- On envoie la liste des clients triée
        ]);
    }
}