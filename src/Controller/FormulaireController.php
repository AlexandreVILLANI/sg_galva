<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FormulaireController extends AbstractController
{
    #[Route('/fiche-dechargement', name: 'app_fiche_dechargement', methods: ['GET', 'POST'])]
    public function dechargement(ClientRepository $clientRepository, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $clientId = $request->request->get('client');
            $dateCloture = $request->request->get('date_cloture');
            $observations = $request->request->get('observations');
            
            $nbPaquetsArray = $request->request->all('nb_paquets'); 
            $emplacementsArray = $request->request->all('emplacement'); 

            $photos = $request->files->get('photos');

            $this->addFlash('success', 'La fiche de déchargement a bien été enregistrée.');
            return $this->redirectToRoute('app_fiche_dechargement');
        }
        $clients = $clientRepository->findAll();

        return $this->render('formulaire/dechargement.html.twig', [
            'clients' => $clients
        ]);
    }
}