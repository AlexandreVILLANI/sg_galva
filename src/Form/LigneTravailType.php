<?php

namespace App\Form;

use App\Entity\LigneDechargement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LigneTravailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On définit ici les classes CSS pour garder ton joli style
        $builder
            ->add('u', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'cell-input center', 'placeholder' => '-']
            ])
            ->add('reference', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'cell-input', 'placeholder' => '-']
            ])
            ->add('travauxAnnexes', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'cell-input', 'placeholder' => '-']
            ])
            ->add('poids', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => 'cell-input right', 'placeholder' => '0.00', 'step' => '0.01']
            ])
            
            // --- C'EST ICI QU'ON AJOUTE LE PRIX À LA TONNE ---
            ->add('prixTonne', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'cell-input center', 
                    'placeholder' => '€/T', 
                    'step' => '0.01'
                ]
            ])
            
            ->add('observations', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'cell-input', 'rows' => 1, 'placeholder' => '-']
            ])
            ->add('commentairesFacturation', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'cell-input', 
                    'placeholder' => 'Comm. Fact.',
                    'style' => 'color: #dc2626; font-weight: bold;' // Red styling
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LigneDechargement::class,
        ]);
    }
}