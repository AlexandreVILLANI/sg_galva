<?php

namespace App\Controller;

use App\Entity\DechargementUrgence;
use App\Form\PeseeUrgenceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pesee/urgence')]
class PeseeUrgenceController extends AbstractController
{
    #[Route('/{id}/edit', name: 'app_pesee_urgence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DechargementUrgence $urgence, EntityManagerInterface $em): Response
    {
        // On s'assure qu'on est bien un utilisateur ayant le droit pesée
        $this->denyAccessUnlessGranted('ROLE_PESEE');

        $form = $this->createForm(PeseeUrgenceType::class, $urgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Poids et observations enregistrés avec succès.');
            return $this->redirectToRoute('app_pesee_home');
        }

        return $this->render('pesee_urgence/edit.html.twig', [
            'urgence' => $urgence,
            'form' => $form->createView(),
        ]);
    }
}
