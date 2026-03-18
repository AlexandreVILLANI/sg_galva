<?php

namespace App\Controller;

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
            'clients' => $clientRepo->findAll() // 3. On envoie les clients
        ]);
    }

    #[Route('/reception-terrain/bon-commande/modifier/{id}', name: 'app_bon_commande_edit')]
    public function edit(
        BonDeCommande $bon, 
        ClientRepository $clientRepo, // On injecte ici aussi
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
            'clients' => $clientRepo->findAll() // On envoie les clients
        ]);
    }

    /**
     * Route API pour mettre à jour le client de la fiche (appelée par le JS)
     */
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
            return new JsonResponse(['succes' => true]);
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

    private function handlePhotosUpload($form, $bon, $em)
    {
        $imageFiles = $form->get('imageFiles')->getData();
        if ($imageFiles) {
            foreach ($imageFiles as $imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.projectf_dir').'/public/uploads/bons',
                        $newFileName
                    );
                    $photo = new PhotoBonCommande();
                    $photo->setNomFichier($newFilename);
                    $bon->addPhoto($photo);
                } catch (\Exception $e) {
                    // Optionnel : logger l'erreur si le déplacement échoue
                }
            }
        }
    }

    #[Route('/reception-terrain/bon-commande/supprimer/{id}', name: 'app_bon_commande_delete', methods: ['POST'])]
    public function delete(BonDeCommande $bon, Request $request, EntityManagerInterface $em): Response
    {
        // Vérification du jeton CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$bon->getId(), $request->request->get('_token'))) {
            $em->remove($bon);
            $em->flush();
            $this->addFlash('success', 'Le bon de commande a été supprimé.');
        }

        return $this->redirectToRoute('app_reception_terrain_home', ['section' => 'list-scans']);
    }
}