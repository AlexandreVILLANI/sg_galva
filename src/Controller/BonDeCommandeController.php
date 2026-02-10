<?php

namespace App\Controller;

use App\Entity\BonDeCommande;
use App\Entity\PhotoBonCommande;
use App\Form\BonDeCommandeType;
use App\Repository\BonDeCommandeRepository;
use App\Repository\FicheDechargementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BonDeCommandeController extends AbstractController
{
    // ... (La méthode index reste inchangée) ...
    #[Route('/reception-terrain/home', name: 'app_reception_terrain_home')]
    public function index(BonDeCommandeRepository $bcRepo, FicheDechargementRepository $ficheRepo): Response 
    {
        // ... (Ton code de filtres inchangé) ...
        return $this->render('reception/home.html.twig', [
            'bons' => $bcRepo->findBy([], ['date' => 'DESC']),
            // ...
        ]);
    }

    #[Route('/reception-terrain/bon-commande/creer/{ficheId}', name: 'app_bon_commande_new')]
    public function new(
        int $ficheId,
        FicheDechargementRepository $ficheRepo,
        BonDeCommandeRepository $bcRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $fiche = $ficheRepo->find($ficheId);
        if (!$fiche) throw $this->createNotFoundException('Fiche introuvable.');

        $bon = new BonDeCommande();
        $bon->setFiche($fiche);
        $bon->setClient($fiche->getClient());
        $bon->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        
        // ON A SUPPRIMÉ LE SETUSER ICI, COMME DEMANDÉ
        
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
        return $this->render('bon_commande/new.html.twig', ['form' => $form->createView(), 'fiche' => $fiche]);
    }

    // ... (edit et delete inchangés) ...
    #[Route('/reception-terrain/bon-commande/modifier/{id}', name: 'app_bon_commande_edit')]
    public function edit(BonDeCommande $bon, Request $request, EntityManagerInterface $em): Response
    {
        // ... (Code standard inchangé)
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotosUpload($form, $bon, $em);
            $em->flush();
            $this->addFlash('success', "Mis à jour.");
            return $this->redirectToRoute('app_reception_terrain_home', ['section' => 'list-scans']);
        }
        return $this->render('bon_commande/edit.html.twig', ['form' => $form->createView(), 'fiche' => $bon->getFiche(), 'bon' => $bon]);
    }
    
    #[Route('/reception-terrain/bon-commande/supprimer/{id}', name: 'app_bon_commande_delete', methods: ['POST'])]
    public function delete(BonDeCommande $bon, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bon->getId(), $request->request->get('_token'))) {
            $em->remove($bon);
            $em->flush();
        }
        return $this->redirectToRoute('app_reception_terrain_home', ['section' => 'list-scans']);
    }

    private function handlePhotosUpload($form, $bon, $em)
    {
        $imageFiles = $form->get('imageFiles')->getData();
        if ($imageFiles) {
            foreach ($imageFiles as $imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir').'/public/uploads/bons', $newFilename);
                $photo = new PhotoBonCommande();
                $photo->setNomFichier($newFilename);
                $bon->addPhoto($photo);
            }
        }
    }

    /**
     * Page de modification spécifique pour l'Ordonnancement (Dali)
     */
    #[Route('/reception-ordonnancement/modifier/{id}', name: 'app_bon_commande_ordo_edit')]
    #[IsGranted('ROLE_RECEPTION_ORDONNANCEMENT')]
    public function editOrdo(Request $request, BonDeCommande $bon, EntityManagerInterface $entityManager): Response 
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
        ]);
    }
}