<?php

namespace App\Controller;

// --- AJOUT DES CLASSES DE COMPRESSION ---
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use App\Entity\FicheDechargement;
use App\Entity\PhotoDechargement;
use App\Entity\Client; 
use App\Form\FicheDechargementType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse; 
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FormulaireController extends AbstractController
{
    #[Route('/fiche-dechargement', name: 'app_fiche_dechargement', methods: ['GET', 'POST'])]
    public function dechargement(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger
    ): Response {
        $fiche = new FicheDechargement();
        $fiche->setCariste($this->getUser());

        $form = $this->createForm(FicheDechargementType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                // Initialisation du moteur
                $manager = new ImageManager(new Driver());
                $destinationFolder = $this->getParameter('fiches_directory'); // Chemin configuré dans services.yaml

                if (!is_dir($destinationFolder)) {
                    mkdir($destinationFolder, 0777, true);
                }

                foreach ($imageFiles as $imageFile) {
                    // Forcer JPG
                    $newFilename = uniqid().'.jpg';
                    $destinationPath = $destinationFolder . '/' . $newFilename;

                    try {
                        // Compression et sauvegarde
                        $image = $manager->read($imageFile->getPathname());
                        $image->scaleDown(width: 1200);
                        $image->save($destinationPath, quality: 75);

                        $photo = new PhotoDechargement();
                        $photo->setNomFichier($newFilename);
                        $fiche->addPhoto($photo);
                    } catch (\Exception $e) {
                        // On relève l'erreur pour ne pas qu'elle soit silencieuse si besoin
                        error_log("Erreur lors de la sauvegarde de l'image : " . $e->getMessage());
                    }
                }
            }

            $total = 0;
            foreach ($fiche->getLignes() as $ligne) {
                $total += $ligne->getNbPaquets();
            }
            $fiche->setTotalPaquets($total);

            $em->persist($fiche);
            $em->flush();

            $this->addFlash('success', 'La fiche de déchargement a bien été enregistrée.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('formulaire/dechargement.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reception/dechargement/{id}/modifier', name: 'app_dechargement_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(FicheDechargement $fiche, Request $request, EntityManagerInterface $em): Response
    {
        // On crée le formulaire avec les données de la fiche existante
        $form = $this->createForm(FicheDechargementType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                $manager = new ImageManager(new Driver());
                $destinationFolder = $this->getParameter('fiches_directory');
                if (!is_dir($destinationFolder)) {
                    mkdir($destinationFolder, 0777, true);
                }
                foreach ($imageFiles as $imageFile) {
                    $newFilename = uniqid().'.jpg';
                    $destinationPath = $destinationFolder . '/' . $newFilename;
                    try {
                        $image = $manager->read($imageFile->getPathname());
                        $image->scaleDown(width: 1200);
                        $image->save($destinationPath, quality: 75);

                        $photo = new PhotoDechargement();
                        $photo->setNomFichier($newFilename);
                        $fiche->addPhoto($photo);
                    } catch (\Exception $e) {
                        error_log("Erreur lors de la sauvegarde de l'image (edit) : " . $e->getMessage());
                    }
                }
            }

            // On recalcule le total des paquets au cas où
            $total = 0;
            foreach ($fiche->getLignes() as $ligne) {
                $total += $ligne->getNbPaquets();
            }
            $fiche->setTotalPaquets($total);

            $em->flush();

            $this->addFlash('success', 'La fiche de déchargement a été mise à jour.');
            return $this->redirectToRoute('app_home'); // Retour au dashboard cariste
        }

        // IMPORTANT : On utilise le template de création 'formulaire/dechargement.html.twig'
        return $this->render('formulaire/dechargement.html.twig', [
            'form' => $form->createView(),
            'fiche' => $fiche,
            'editMode' => true // Pour pouvoir changer le titre en "Modification" dans le Twig
        ]);
    }

    #[Route('/reception/dechargement/{id}/supprimer', name: 'app_fiche_dechargement_delete', methods: ['POST'])]
    // Optionnel : #[IsGranted('ROLE_USER')] si tu veux sécuriser
    public function deleteFiche(Request $request, FicheDechargement $fiche, EntityManagerInterface $em): Response
    {
        // Vérification du jeton CSRF de sécurité
        if ($this->isCsrfTokenValid('delete_fiche'.$fiche->getId(), $request->request->get('_token'))) {
            
            // On supprime la fiche de la base de données
            $em->remove($fiche);
            $em->flush();
            
            $this->addFlash('success', 'La fiche de déchargement a été supprimée avec succès.');
        }

        // On le redirige vers son espace cariste
        return $this->redirectToRoute('app_home');
    }

    

    #[Route('/reception/dechargement/{id}', name: 'app_dechargement_show')]
    #[IsGranted('ROLE_RECEPTION_TERRAIN')]
    public function show(FicheDechargement $fiche, ClientRepository $clientRepo): Response
    {
        $clients = $clientRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('formulaire/show.html.twig', [
            'fiche' => $fiche,
            'clients' => $clients 
        ]);
    }

    #[Route('/reception/dechargement/{id}/update-client', name: 'app_dechargement_update_client', methods: ['POST'])]
    public function updateClient(Request $request, FicheDechargement $fiche, ClientRepository $clientRepo, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $clientId = $data['client_id'] ?? null;

        if ($clientId) {
            $client = $clientRepo->find($clientId);
            if ($client) {
                $fiche->setClient($client);
                $em->flush();
                
                return $this->json([
                    'success' => true, 
                    'clientName' => $client->getNom()
                ]);
            }
        }

        return $this->json(['success' => false, 'message' => 'Client introuvable'], 400);
    }

    #[Route('/reception/client/new-ajax', name: 'app_client_new_ajax', methods: ['POST'])]
    public function newClientAjax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);        
        $nom = $data['nom'] ?? null;
        $tel = $data['telephone'] ?? null;

        if (!$nom) {
            return $this->json(['success' => false, 'message' => 'Le nom est obligatoire.'], 400);
        }
        $client = new Client();
        $client->setNom($nom);
        if ($tel) {
            $client->setTelephone($tel);
        }

        $em->persist($client);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'client' => [
                'id' => $client->getId(),
                'nom' => $client->getNom()
            ]
        ]);
    }
}