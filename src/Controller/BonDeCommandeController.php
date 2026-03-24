<?php

namespace App\Controller;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use App\Entity\BonDeCommande;
use App\Entity\PhotoBonCommande;

use App\Form\BonDeCommandeType;

use App\Repository\BonDeCommandeRepository;
use App\Repository\FicheDechargementRepository;
use App\Repository\ClientRepository; 

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BonDeCommandeController extends AbstractController
{
    #[Route('/reception-terrain/bon-commande/creer/{ficheId}', name: 'app_bon_commande_new')]
    public function new(
        int $ficheId,
        FicheDechargementRepository $ficheRepo,
        BonDeCommandeRepository $bcRepo,
        ClientRepository $clientRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $fiche = $ficheRepo->find($ficheId);
        if (!$fiche) throw $this->createNotFoundException('Fiche introuvable.');

        $bon = new BonDeCommande();
        $bon->setFiche($fiche);
        $bon->setClient($fiche->getClient());
        $bon->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        
        // --- LOGIQUE REFI ---
        $lastRefi = $bcRepo->getLastRefi(); 
        $nextNumber = 1;
        if ($lastRefi) {
            preg_match_all('/\d+/', $lastRefi, $matches);
            $nextNumber = (int) end($matches[0]) + 1;
        }
        $formattedRefi = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        $bon->setRefi($formattedRefi);

        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotosUpload($form, $bon, $em);
            $em->persist($bon);
            $em->flush();
            return $this->redirectToRoute('app_reception_terrain_home', ['section' => 'list-scans']);
        }

        return $this->render('bon_commande/new.html.twig', [
            'form' => $form->createView(), 
            'fiche' => $fiche,
            'clients' => $clientRepo->findAll() 
        ]);
    }

    #[Route('/reception-terrain/bon-commande/modifier/{id}', name: 'app_bon_commande_edit')]
    public function edit(
        BonDeCommande $bon, 
        ClientRepository $clientRepo,
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotosUpload($form, $bon, $em);
            $em->flush();
            $this->addFlash('success', "Mis à jour.");
            return $this->redirectToRoute('app_reception_terrain_home', ['section' => 'list-scans']);
        }

        return $this->render('bon_commande/edit.html.twig', [
            'form' => $form->createView(), 
            'fiche' => $bon->getFiche(), 
            'bon' => $bon,
            'clients' => $clientRepo->findAll() 
        ]);
    }

    #[Route('/reception/dechargement/{id}/update-client', name: 'api_update_client_fiche', methods: ['POST'])]
    public function updateClientFiche(Request $request, int $id, FicheDechargementRepository $ficheRepo, ClientRepository $clientRepo, EntityManagerInterface $em): JsonResponse
    {
        $fiche = $ficheRepo->find($id);
        $data = json_decode($request->getContent(), true);
        $clientId = $data['client_id'] ?? null;

        if (!$fiche || !$clientId) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides.'], 400);
        }

        $client = $clientRepo->find($clientId);
        if ($client) {
            $fiche->setClient($client);
            $em->flush();
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Client non trouvé.'], 404);
    }

    #[Route('/reception-ordonnancement/modifier/{id}', name: 'app_bon_commande_ordo_edit')]
    #[IsGranted('ROLE_RECEPTION_ORDONNANCEMENT')]
    public function editOrdo(Request $request, BonDeCommande $bon, ClientRepository $clientRepo, EntityManagerInterface $entityManager): Response 
    {
        $fiche = $bon->getFiche(); 
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bon->setIsValidatedOrdo(true);
            $bon->setValidatedAtOrdo(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $entityManager->flush();
            $this->addFlash('success', 'Bon validé pour production.');
            return $this->redirectToRoute('app_reception_ordonnancement_home', ['section' => 'list-bons-ordo']);
        }

        return $this->render('reception_ordonnancement/bon_commande/edit.html.twig', [
            'bon' => $bon,
            'fiche' => $fiche,
            'form' => $form->createView(),
            'clients' => $clientRepo->findAll() 
        ]);
    }

    // --- LA FONCTION DE COMPRESSION EST ICI ---
    private function handlePhotosUpload($form, $bon, $em)
    {
        $imageFiles = $form->get('imageFiles')->getData();
        
        if ($imageFiles) {
            // Initialisation du moteur d'image
            $manager = new ImageManager(new Driver());
            $destinationFolder = $this->getParameter('kernel.project_dir').'/public/uploads/bons';

            foreach ($imageFiles as $imageFile) {
                // On force l'extension JPG pour la légèreté
                $newFilename = uniqid().'.jpg';
                $destinationPath = $destinationFolder . '/' . $newFilename;
                
                try {
                    // Lecture de l'image envoyée
                    $image = $manager->read($imageFile->getPathname());
                    
                    // Redimensionnement à max 1200px de large (garde les proportions)
                    $image->scaleDown(width: 1200);
                    
                    // Sauvegarde avec 75% de qualité
                    $image->save($destinationPath, quality: 75);
                    
                    $photo = new PhotoBonCommande();
                    $photo->setNomFichier($newFilename);
                    $bon->addPhoto($photo);
                    
                    $em->persist($photo); 
                    
                } catch (\Exception $e) {
                    dd("Erreur lors de la compression : " . $e->getMessage());
                }
            }
        }
    }

    // --- NOUVELLE ROUTE POUR SUPPRIMER UNE PHOTO (AJAX) ---
    #[Route('/reception-terrain/bon-commande/photo/{id}/supprimer', name: 'app_bon_commande_photo_delete', methods: ['POST'])]
    public function deletePhoto(int $id, EntityManagerInterface $em): JsonResponse
    {
        $photo = $em->getRepository(PhotoBonCommande::class)->find($id);

        if (!$photo) {
            return new JsonResponse(['success' => false, 'message' => 'Photo introuvable.'], 404);
        }

        try {
            // Suppression du fichier physique
            $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/bons/' . $photo->getNomFichier();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Suppression en base de données
            $em->remove($photo);
            $em->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur lors de la suppression.'], 500);
        }
    }

    #[Route('/reception-terrain/bon-commande/supprimer/{id}', name: 'app_bon_commande_delete', methods: ['POST'])]
    public function delete(BonDeCommande $bon, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bon->getId(), $request->request->get('_token'))) {
            $em->remove($bon);
            $em->flush();
            $this->addFlash('success', 'Le bon de commande a été supprimé.');
        }

        return $this->redirectToRoute('app_reception_terrain_home', ['section' => 'list-scans']);
    }
}