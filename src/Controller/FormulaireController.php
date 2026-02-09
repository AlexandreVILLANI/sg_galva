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

class FormulaireController extends AbstractController
{
    #[Route('/fiche-dechargement', name: 'app_fiche_dechargement', methods: ['GET', 'POST'])]
    public function dechargement(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger
    ): Response {
        // 1. On prépare la fiche
        $fiche = new FicheDechargement();
        $fiche->setCariste($this->getUser()); // On lie le cariste connecté

        // 2. On crée le formulaire "intelligent"
        $form = $this->createForm(FicheDechargementType::class, $fiche);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                dump($error->getMessage());
            }
            dd('Fin du diagnostic');
        }

        // 3. Si le formulaire est envoyé et valide
        if ($form->isSubmitted() && $form->isValid()) {
            
            // Gestion des photos
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

            // Calcul du total automatique
            $total = 0;
            foreach ($fiche->getLignes() as $ligne) {
                $total += $ligne->getNbPaquets();
            }
            $fiche->setTotalPaquets($total);

            // Enregistrement final
            $em->persist($fiche);
            $em->flush();

            $this->addFlash('success', 'La fiche de déchargement a bien été enregistrée.');
            return $this->redirectToRoute('app_fiche_dechargement');
        }

        return $this->render('formulaire/dechargement.html.twig', [
            'form' => $form->createView(), // On passe l'objet form au template
        ]);
    }

    #[Route('/reception/dechargement/{id}', name: 'app_dechargement_show')]
    #[IsGranted('ROLE_RECEPTION_TERRAIN')]
    public function show(FicheDechargement $fiche): Response
    {
        return $this->render('formulaire/show.html.twig', [
            'fiche' => $fiche
        ]);
    }
}