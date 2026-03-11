<?php

namespace App\Controller;

use App\Entity\FicheDechargement;
use App\Entity\PhotoDechargement;
use App\Form\FicheDechargementType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                foreach ($imageFiles as $imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move($this->getParameter('fiches_directory'), $newFilename);

                    $photo = new PhotoDechargement();
                    $photo->setNomFichier($newFilename);
                    $fiche->addPhoto($photo);
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
            return $this->redirectToRoute('app_fiche_dechargement');
        }

        return $this->render('formulaire/dechargement.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reception/dechargement/{id}', name: 'app_dechargement_show')]
    #[IsGranted('ROLE_RECEPTION_TERRAIN')] // Vérifie tes rôles ici si besoin
    public function show(FicheDechargement $fiche, ClientRepository $clientRepo): Response
    {
        // On récupère tous les clients triés par nom pour le menu déroulant
        $clients = $clientRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('formulaire/show.html.twig', [
            'fiche' => $fiche,
            'clients' => $clients // On envoie la liste à la vue
        ]);
    }

    // --- NOUVELLE ROUTE : Mise à jour du client en AJAX ---
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
}