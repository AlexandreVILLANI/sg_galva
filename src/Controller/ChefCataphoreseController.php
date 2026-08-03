<?php

namespace App\Controller;

use App\Entity\BonTravail;
use App\Form\CataphoreseBonTravailType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chef-cataphorese')]
#[IsGranted('ROLE_CHEF_CATAPHORESE')]
class ChefCataphoreseController extends AbstractController
{
    #[Route('/bon-travail/{id}/edit', name: 'app_chef_cataphorese_bt_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BonTravail $bonTravail, EntityManagerInterface $entityManager): Response
    {
        // On s'assure que c'est bien une commande cataphorèse
        if (!$bonTravail->getBonCommande()->isCataphorese()) {
            $this->addFlash('error', 'Ce bon de travail ne concerne pas la cataphorèse.');
            return $this->redirectToRoute('app_chef_cataphorese_home');
        }

        $form = $this->createForm(CataphoreseBonTravailType::class, $bonTravail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Observations de cataphorèse enregistrées.');
            return $this->redirectToRoute('app_chef_cataphorese_home');
        }

        return $this->render('chef_cataphorese/edit.html.twig', [
            'bonTravail' => $bonTravail,
            'form' => $form->createView(),
        ]);
    }
}
