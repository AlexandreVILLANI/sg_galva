<?php

namespace App\Form;

use App\Entity\BonLivraison;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonLivraisonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateEnlevement', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date d\'enlèvement',
                'attr' => ['class' => 'ghost-input']
            ])
            ->add('transporteur', TextType::class, [
                'required' => false,
                'label' => 'Transporteur',
                'attr' => ['class' => 'ghost-input']
            ])
            ->add('chauffeur', TextType::class, [
                'required' => false,
                'label' => 'Nom du chauffeur',
                'attr' => ['class' => 'ghost-input']
            ])
            ->add('immatriculation', TextType::class, [
                'required' => false,
                'label' => 'Immatriculation',
                'attr' => ['class' => 'ghost-input']
            ])
            // Ce champ est CACHÉ car il sera rempli automatiquement par le script de la tablette
            ->add('signature', HiddenType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonLivraison::class,
        ]);
    }
}