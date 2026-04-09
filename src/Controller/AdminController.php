<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\BonDeCommande;
use App\Entity\FicheDechargement;
use App\Entity\BonTravail;
use App\Entity\User;
use App\Entity\BonLivraison;

use App\Form\BonDeCommandeType;
use App\Form\ClientType;
use App\Form\UserEditType;
use App\Form\UserType;
use App\Form\BonLivraisonType;

use App\Repository\BonDeCommandeRepository;
use App\Repository\LigneDechargementRepository;
use App\Repository\ClientRepository;
use App\Repository\FicheDechargementRepository;
use App\Repository\BonTravailRepository;
use App\Repository\UserRepository;
use App\Repository\EmplacementRepository;
use App\Repository\PlanningRepository;
use App\Repository\BonLivraisonRepository; 

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
        BonTravailRepository $btRepo,
        PlanningRepository $planningRepo,
        UserRepository $userRepo,
        BonLivraisonRepository $blRepo
    ): Response {
        return $this->render('home/admin.html.twig', [
            'bons' => $bcRepo->findBy([], ['date' => 'DESC']),
            'fiches' => $ficheRepo->findBy([], ['date' => 'DESC']), 
            'clients' => $clientRepo->findBy([], ['nom' => 'ASC']),
            'bons_travail' => $btRepo->findAll(),
            'plannings' => $planningRepo->findBy([], ['datePlanning' => 'DESC']), 
            'users' => $userRepo->findBy([], ['id' => 'ASC']),
            'bons_livraison' => $blRepo->findBy([], ['dateCreation' => 'DESC']),
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
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->request->get('_token'))) {
            
            try {
                // On essaie de supprimer le client
                $em->remove($client);
                $em->flush();
                $this->addFlash('success', 'Le client "' . $client->getNom() . '" a été supprimé.');
                
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                // Si la base de données bloque à cause de l'historique, on attrape l'erreur !
                $this->addFlash('error', '🛑 Impossible de supprimer ce client : il possède déjà des Fiches de Déchargement ou des Bons de Commande dans l\'historique.');
            }
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
        // 1. SAUVEGARDE DU TRAITEMENT
        $isCataRequest = $request->request->get('is_cataphorese');
        
        // Si l'info arrive bien du formulaire (n'est pas nulle)
        if ($isCataRequest !== null) {
            $isCata = ($isCataRequest === '1'); 
            
            $commande = $bt->getBonCommande();
            if ($commande) {
                // On met à jour selon les setters de ton fichier BonDeCommande.php
                $commande->setIsCataphorese($isCata);
                $commande->setIsGalvanisation(!$isCata); // Si l'un est vrai, l'autre est faux
                
                $em->persist($commande); 
            }
        }

        // 2. INFOS GÉNÉRALES
        $bt->setNumero($request->request->get('numero'));
        $bt->setExigenceParticuliere($request->request->get('exigence_particuliere'));
        $bt->setRepriseUsinage($request->request->get('reprise_usinage'));
        $bt->setObservations($request->request->get('observations'));
        
        $delai = $request->request->get('delai_client');
        if ($delai) {
            $bt->setDelaiClient(new \DateTimeImmutable($delai));
        } else {
            $bt->setDelaiClient(null);
        }
        
        // 3. LIGNES DU BT
        $lignesData = $request->request->all('lignes');
        if (!empty($lignesData)) {
            foreach ($bt->getLignes() as $ligne) {
                $ligneId = $ligne->getId();
                if (isset($lignesData[$ligneId])) {
                    $data = $lignesData[$ligneId];
                    $ligne->setU($data['u'] ?? '');
                    $ligne->setReference($data['reference'] ?? '');
                    $ligne->setTravauxAnnexes($data['travauxAnnexes'] ?? '');
                    $ligne->setObservations($data['observations'] ?? '');
                    
                    $poidsSaisi = str_replace(',', '.', $data['poids'] ?? '0');
                    $ligne->setPoids((float) $poidsSaisi);
                    
                    $prixSaisi = str_replace(',', '.', $data['prixTonne'] ?? '0');
                    $ligne->setPrixTonne((float) $prixSaisi);
                }
            }
        }

        // 4. SAUVEGARDE FINALE
        $em->flush(); 
        
        $this->addFlash('success', 'Mi se à jour réussie. Le traitement a été synchronisé !');
        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-bt']);
    }

    #[Route('/dechargement/{id}', name: 'app_admin_dechargement_edit', methods: ['GET', 'POST'])]
    public function editDechargement(
        Request $request, 
        FicheDechargement $fiche, 
        EntityManagerInterface $em,
        ClientRepository $clientRepo,
        EmplacementRepository $empRepo
    ): Response {
        
        if ($request->isMethod('POST')) {
            // 1. Date et Observations
            $fiche->setObservations($request->request->get('observations'));
            
            // 2. Client
            if ($clientId = $request->request->get('client_id')) {
                $fiche->setClient($clientRepo->find($clientId));
            }

            // 3. Lignes (Paquets, description, emplacement)
            $lignesData = $request->request->all('lignes');
            if (!empty($lignesData)) {
                $nouveauTotal = 0;
                foreach ($fiche->getLignes() as $ligne) {
                    $ligneId = $ligne->getId();
                    if (isset($lignesData[$ligneId])) {
                        $data = $lignesData[$ligneId];
                        $ligne->setNbPaquets((int) $data['qte']);
                        $ligne->setDescription($data['desc']);
                        
                        if (!empty($data['emplacement'])) {
                            $ligne->setEmplacement($empRepo->find($data['emplacement']));
                        }
                        $nouveauTotal += (int) $data['qte'];
                    }
                }
                $fiche->setTotalPaquets($nouveauTotal);
            }

            $em->flush();
            $this->addFlash('success', 'Fiche mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-fd']);
        }

        // ON NE CHARGE PLUS LES CARISTES ICI POUR EVITER L'ERREUR SQL
        return $this->render('formulaire/admin.html.twig', [
            'fiche' => $fiche,
            'clients' => $clientRepo->findBy([], ['nom' => 'ASC']),
            'emplacements' => $empRepo->findAll() 
        ]);
    }

    #[Route('/utilisateur/{id}/modifier', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // --- MAGIE DE SYNCHRONISATION DES RÔLES ---
            // On récupère le nom du rôle choisi dans le formulaire
            $nomRole = $user->getUserRole() ? $user->getUserRole()->getNom() : '';
            
            // On le traduit en rôle technique pour la sécurité Symfony
            $roleTechnique = match($nomRole) {
                'Administrateur' => 'ROLE_ADMIN',
                'Cariste' => 'ROLE_CARISTE',
                'Réception Terrain' => 'ROLE_RECEPTION_TERRAIN',
                'Réception Ordonnancement' => 'ROLE_RECEPTION_ORDONNANCEMENT',
                'Ordonnancement Planning' => 'ROLE_ORDONNANCEMENT',
                'Chef d\'Équipe' => 'ROLE_CHEF_EQUIPE',
                'Équipe Colisage' => 'ROLE_COLISAGE',
                default => 'ROLE_USER'
            };
            
            // On applique le rôle technique à l'utilisateur
            $user->setRoles([$roleTechnique]);

            $em->flush();
            $this->addFlash('success', 'Le compte de ' . $user->getPrenom() . ' a été mis à jour.');
            
            // On redirige vers le tableau de bord, onglet "Utilisateurs"
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-us']);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/utilisateur/nouveau', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function newUser(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. CRYPTAGE DU MOT DE PASSE
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);

            // 2. SYNCHRONISATION DU RÔLE
            $nomRole = $user->getUserRole() ? $user->getUserRole()->getNom() : '';
            $roleTechnique = match($nomRole) {
                'Administrateur' => 'ROLE_ADMIN',
                'Cariste' => 'ROLE_CARISTE',
                'Réception Terrain' => 'ROLE_RECEPTION_TERRAIN',
                'Réception Ordonnancement' => 'ROLE_RECEPTION_ORDONNANCEMENT',
                'Ordonnancement Planning' => 'ROLE_ORDONNANCEMENT',
                'Chef d\'Équipe' => 'ROLE_CHEF_EQUIPE',
                'Équipe Colisage' => 'ROLE_COLISAGE',
                default => 'ROLE_USER'
            };
            $user->setRoles([$roleTechnique]);

            // 3. SAUVEGARDE EN BASE DE DONNÉES
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Le compte de ' . $user->getPrenom() . ' a été créé avec succès.');
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-us']);
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/utilisateur/{id}/supprimer', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $em): Response
    {
        // 1. Vérification du jeton de sécurité (CSRF)
        if ($this->isCsrfTokenValid('delete_user'.$user->getId(), $request->request->get('_token'))) {
            
            // 2. Protection contre l'auto-suppression
            if ($user->getId() === $this->getUser()->getId()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
                return $this->redirectToRoute('app_admin_home', ['section' => 'admin-us']);
            }

            try {
                // 3. Essai de suppression
                $em->remove($user);
                $em->flush();
                $this->addFlash('success', 'Le compte de ' . $user->getPrenom() . ' a été supprimé définitivement.');
                
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                // 4. Si l'utilisateur est lié à des fiches ou des bons dans l'historique
                $this->addFlash('error', '🛑 Impossible de supprimer ce compte : ' . $user->getPrenom() . ' a déjà créé des documents (Fiches ou Bons) dans le système. Son compte doit être conservé pour l\'historique.');
            }
        }

        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-us']);
    }

    /**
     * AFFICHER ET ÉDITER LE BON DE LIVRAISON (Vue Admin)
     */
    #[Route('/bon-livraison/{id}', name: 'app_admin_bl_show', methods: ['GET', 'POST'])]
    public function showBonLivraison(Request $request, BonLivraison $bl, EntityManagerInterface $em): Response
    {
        // On récupère le bon de travail et la commande associés pour l'affichage
        $bt = $bl->getBonTravail();
        $commande = $bt->getBonCommande();

        // On crée le formulaire pour éditer les infos de transport et la signature
        $form = $this->createForm(BonLivraisonType::class, $bl);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Si une nouvelle signature a été faite, on passe le statut à "signé"
            if ($bl->getSignature()) {
                $bl->setStatut('Signé'); 
            }

            $em->flush();
            $this->addFlash('success', 'Le Bon de Livraison a bien été enregistré.');

            // Redirection vers l'affichage du BL (la même page) pour voir la modif
            return $this->redirectToRoute('app_admin_bl_show', ['id' => $bl->getId()]);
        }

        return $this->render('bon_livraison/admin.html.twig', [
            'bl' => $bl,
            'bt' => $bt,
            'commande' => $commande,
            'form' => $form->createView(),
        ]);
    }
}