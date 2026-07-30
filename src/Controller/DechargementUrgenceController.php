<?php

namespace App\Controller;

use App\Entity\DechargementUrgence;
use App\Entity\PhotoUrgence;
use App\Form\DechargementUrgenceType;
use App\Repository\DechargementUrgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cariste/urgence')]
class DechargementUrgenceController extends AbstractController
{
    #[Route('/', name: 'app_urgence_index', methods: ['GET'])]
    public function index(DechargementUrgenceRepository $repo): Response
    {
        return $this->render('urgence/index.html.twig', [
            'urgences' => $repo->findBy([], ['dateCreation' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_urgence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $urgence = new DechargementUrgence();
        $urgence->setStatut('PROVISOIRE');

        $form = $this->createForm(DechargementUrgenceType::class, $urgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotosUpload($request, $urgence, $em);
            $em->persist($urgence);
            $em->flush();

            $this->addFlash('success', 'Déchargement d\'urgence enregistré avec succès.');
            return $this->redirectToRoute('app_home', ['section' => 'cariste-urgence']);
        }

        return $this->render('urgence/new.html.twig', [
            'urgence' => $urgence,
            'form' => $form->createView(),
        ]);
    }

    private function handlePhotosUpload(Request $request, DechargementUrgence $urgence, EntityManagerInterface $em)
    {
        $imageFiles = $request->files->get('imageFiles');
        
        if ($imageFiles) {
            $manager = new ImageManager(new Driver());
            $destinationFolder = $this->getParameter('kernel.project_dir').'/public/uploads/urgences';

            if (!is_dir($destinationFolder)) {
                mkdir($destinationFolder, 0777, true);
            }

            foreach ($imageFiles as $imageFile) {
                if (!$imageFile) continue;
                
                $mimeType = $imageFile->getMimeType();

                if (str_starts_with($mimeType, 'image/')) {
                    $newFilename = uniqid().'.jpg';
                    $destinationPath = $destinationFolder . '/' . $newFilename;
                    
                    try {
                        $image = $manager->read($imageFile->getPathname());
                        $image->scale(width: 1200); // Compression/Resize
                        $image->save($destinationPath, quality: 75);

                        $photo = new PhotoUrgence();
                        $photo->setNomFichier($newFilename);
                        $urgence->addPhoto($photo);
                        
                        $em->persist($photo);
                    } catch (\Exception $e) {
                        // Handle error silently or log it
                    }
                }
            }
        }
    }
}
