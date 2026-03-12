<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\BonDeCommande;
use App\Entity\FicheDechargement;
use App\Entity\BonTravail;

use App\Form\BonDeCommandeType;
use App\Form\ClientType;

use App\Repository\BonDeCommandeRepository;
use App\Repository\LigneDechargementRepository;
use App\Repository\ClientRepository;
use App\Repository\FicheDechargementRepository;
use App\Repository\BonTravailRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Doctrine\ORM\EntityManagerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    /**
     * PAGE D'ACCUEIL ADMIN (Centralise toutes les listes)
     */
    #[Route('/', name: 'app_admin_home')]
    public function index(
        BonDeCommandeRepository $bcRepo, 
        FicheDechargementRepository $ficheRepo, 
        ClientRepository $clientRepo,
        BonTravailRepository $btRepo          
    ): Response {
        return $this->render('home/admin.html.twig', [
            'bons' => $bcRepo->findBy([], ['date' => 'DESC']),
            'fiches' => $ficheRepo->findBy([], ['date' => 'DESC']), 
            'clients' => $clientRepo->findBy([], ['nom' => 'ASC']),
            'bons_travail' => $btRepo->findAll(), // <--- Envoie ENFIN les BT !
        ]);
    }

    #[Route('/bon-commande/{id}/edit', name: 'app_admin_bc_edit', methods: ['GET', 'POST'])]
    public function editBonCommande(
        Request $request, 
        BonDeCommande $bon, 
        EntityManagerInterface $em,
        LigneDechargementRepository $ligneRepo,
        ClientRepository $clientRepo 
    ): Response {
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ... gestion changement client ...
            $nouveauClientId = $request->request->get('nouveau_client');
            if ($nouveauClientId) {
                $nouveauClient = $clientRepo->find($nouveauClientId);
                if ($nouveauClient) $bon->getFiche()->setClient($nouveauClient);
            }

            // ... gestion lignes ...
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
            $this->addFlash('success', 'Mise à jour réussie.');
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-bc']);
        }

        return $this->render('bon_commande/admin.html.twig', [
            'form' => $form->createView(),
            'bon' => $bon,
            'fiche' => $bon->getFiche(),
            'clients' => $clientRepo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/admin/client/new', name: 'app_admin_client_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function newClient(Request $request, EntityManagerInterface $em): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($client);
            $em->flush();

            $this->addFlash('success', 'Le client a bien été créé.');
            
            // On redirige vers l'admin en forçant l'onglet client
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
        }

        return $this->render('client/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/client/{id}/delete', name: 'app_admin_client_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteClient(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        // On vérifie le jeton CSRF pour être sûr que la demande vient bien de ton site
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            $em->remove($client);
            $em->flush();

            $this->addFlash('success', 'Le client "' . $client->getNom() . '" a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
    }

    #[Route('/admin/client/{id}/edit', name: 'app_admin_client_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editClient(Request $request, Client $client, EntityManagerInterface $em): Response
    {
        // C'est ici que la magie opère : Symfony voit que $client n'est pas vide,
        // donc il pré-remplit le formulaire avec les données de la BDD.
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de faire $em->persist($client) car l'objet vient déjà de la BDD.
            // On a juste à flush pour enregistrer les modifications.
            $em->flush();

            $this->addFlash('success', 'Le client a été mis à jour.');
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    /**
     * SUPPRIMER UNE FICHE DE DECHARGEMENT
     */
    #[Route('/fiche/{id}/delete', name: 'app_admin_fiche_delete', methods: ['POST'])]
    public function deleteFiche(Request $request, FicheDechargement $fiche, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_fiche'.$fiche->getId(), $request->request->get('_token'))) {
            // Attention : Supprimer une fiche supprimera les photos liées si tu as mis "orphanRemoval=true"
            $em->remove($fiche);
            $em->flush();

            $this->addFlash('success', 'La fiche n°' . $fiche->getId() . ' a été supprimée.');
        }

        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-fd']);
    }

    /**
     * PAGE D'ÉDITION DU BON DE TRAVAIL (Vue Admin)
     */
    #[Route('/bon-travail/{id}/edit', name: 'app_admin_bt_edit', methods: ['GET'])]
    public function editBonTravail(BonTravail $bt): Response 
    {
        return $this->render('bon_travail/admin.html.twig', [
            'bt' => $bt,
            'commande' => $bt->getBonCommande(),
        ]);
    }

    /**
     * SAUVEGARDE DES MODIFICATIONS DU BON DE TRAVAIL
     */
    #[Route('/bon-travail/{id}/update', name: 'app_admin_bt_update', methods: ['POST'])]
    public function updateBT(Request $request, BonTravail $bt, EntityManagerInterface $em): Response
    {
        // 1. Mise à jour des infos générales
        $bt->setNumero($request->request->get('numero'));
        $bt->setExigenceParticuliere($request->request->get('exigence_particuliere'));
        $bt->setRepriseUsinage($request->request->get('reprise_usinage'));
        $bt->setObservations($request->request->get('observations'));
        
        // Gestion du délai
        $delai = $request->request->get('delai_client');
        if ($delai) {
            $bt->setDelaiClient(new \DateTime($delai));
        } else {
            $bt->setDelaiClient(null);
        }

        // 2. Mise à jour des lignes du tableau (Qté, Ref, etc.)
        $lignesData = $request->request->all('lignes');
        
        if (!empty($lignesData)) {
            foreach ($bt->getLignes() as $ligne) {
                $ligneId = $ligne->getId();
                
                // Si on a des données pour cette ligne précise
                if (isset($lignesData[$ligneId])) {
                    $data = $lignesData[$ligneId];
                    $ligne->setU($data['u'] ?? '');
                    $ligne->setReference($data['reference'] ?? '');
                    $ligne->setTravauxAnnexes($data['travauxAnnexes'] ?? '');
                    $ligne->setPoids((float) ($data['poids'] ?? 0));
                    $ligne->setObservations($data['observations'] ?? '');
                }
            }
        }

        // 3. On sauvegarde tout en base de données
        $em->flush();
        $this->addFlash('success', 'Toutes les modifications du Bon de Travail ont été enregistrées.');

        // 4. Redirection vers l'accueil Admin, sur l'onglet Bon de Travail
        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-bt']);
    }
}