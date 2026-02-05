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

class BonDeCommandeController extends AbstractController
{
    /**
     * C'est ici que tes listes de filtres sont générées dynamiquement
     */
    #[Route('/reception/home', name: 'app_reception_home')]
    public function index(BonDeCommandeRepository $bcRepo, FicheDechargementRepository $ficheRepo): Response 
    {
        // --- ÉTAPE 1 : RÉCUPÉRATION BRUTE ---
        $rawForfaits = $bcRepo->createQueryBuilder('b')
            ->select('DISTINCT b.forfait AS name')
            ->where('b.forfait IS NOT NULL')
            ->getQuery()
            ->getScalarResult();
        
        // DEBUG 1 : On regarde ce que la base de données renvoie exactement
        dump('1. Données SQL brutes :', $rawForfaits);

        // --- ÉTAPE 2 : FORMATAGE ---
        $forfaits = array_filter(array_column($rawForfaits, 'name'));
        
        // DEBUG 2 : On regarde si le tableau est bien "aplati" et nettoyé
        dump('2. Tableau après array_column et filter :', $forfaits);

        // --- ÉTAPE 3 : CARISTES (Par précaution) ---
        $caristes = $ficheRepo->createQueryBuilder('f')
            ->select('DISTINCT u.prenom AS name')
            ->join('f.cariste', 'u')
            ->getQuery()
            ->getScalarResult();
        $caristes = array_filter(array_column($caristes, 'name'));

        // Si on arrive ici, on arrête tout et on affiche les résultats à l'écran
        // die('Vérifie les dumps en haut de page !'); 
        
        return $this->render('reception/home.html.twig', [
            'bons' => $bcRepo->findBy([], ['date' => 'DESC']),
            'fiches' => $ficheRepo->findBy([], ['date' => 'DESC']),
            'uniqueForfaits' => $forfaits,
            'uniqueCaristes' => $caristes 
        ]);
    }

    #[Route('/reception/bon-commande/creer/{ficheId}', name: 'app_bon_commande_new')]
    public function new(
        int $ficheId, 
        FicheDechargementRepository $ficheRepo, 
        BonDeCommandeRepository $bcRepo, 
        Request $request, 
        EntityManagerInterface $em
    ): Response {
        $fiche = $ficheRepo->find($ficheId);
        if (!$fiche) throw $this->createNotFoundException('Fiche de déchargement introuvable.');

        $bon = new BonDeCommande();
        $bon->setFiche($fiche);
        $bon->setClient($fiche->getClient());
        $bon->setDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));

        // --- LOGIQUE DU REFI SÉQUENTIEL À 6 CHIFFRES ---
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

            $this->addFlash('success', "Le Bon de Commande n°$formattedRefi a été créé !");
            return $this->redirectToRoute('app_reception_home', ['section' => 'list-scans']);
        }
        return $this->render('bon_commande/new.html.twig', [
            'form' => $form->createView(),
            'fiche' => $fiche
        ]);
    }

    #[Route('/reception/bon-commande/modifier/{id}', name: 'app_bon_commande_edit')]
    public function edit(BonDeCommande $bon, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(BonDeCommandeType::class, $bon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotosUpload($form, $bon, $em);
            $em->flush();

            $this->addFlash('success', "Le Bon de Commande {$bon->getRefi()} a été mis à jour.");
            return $this->redirectToRoute('app_reception_home', ['section' => 'list-scans']);
        }

        return $this->render('bon_commande/edit.html.twig', [
            'form' => $form->createView(),
            'fiche' => $bon->getFiche(),
            'bon' => $bon
        ]);
    }

    #[Route('/reception/bon-commande/supprimer/{id}', name: 'app_bon_commande_delete', methods: ['POST'])]
    public function delete(BonDeCommande $bon, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$bon->getId(), $request->request->get('_token'))) {
            $em->remove($bon);
            $em->flush();
            $this->addFlash('success', 'Le bon de commande a été supprimé.');
        }
        return $this->redirectToRoute('app_reception_home', ['section' => 'list-scans']);
    }

    /**
     * Petite fonction interne pour éviter de répéter le code des photos dans new et edit
     */
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
}