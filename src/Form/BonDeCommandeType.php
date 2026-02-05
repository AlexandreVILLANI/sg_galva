<?php

namespace App\Form;

use App\Entity\BonDeCommande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class BonDeCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('refi', TextType::class, [
                'label' => 'Référence Interne (REFI)',
                'attr' => ['class' => 'form-control', 'readonly' => 'readonly']
            ])
            ->add('forfait', ChoiceType::class, [
                'label' => 'Type de Forfait',
                'choices'  => [
                    'Usinage' => 'Usinage',
                    'Standard' => 'Standard',
                    'Perçage' => 'Perçage',
                    'Montage' => 'Montage',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('nomChauffeur', TextType::class, [
                'required' => false,
                'label' => 'Nom du chauffeur',
                'attr' => ['class' => 'ghost-input', 'placeholder' => '(DOMEZEL LEO)']
            ])
            // --- Le champ pour les photos spécifiques au Bon ---
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos complémentaires',
                'multiple' => true,
                'mapped' => false, // Important : ce champ n'est pas une colonne SQL du BC
                'required' => false,
                'attr' => ['accept' => 'image/*']
            ])
            ->add('isGalvanisation', CheckboxType::class, ['required' => false])
            ->add('isCataphorese', CheckboxType::class, ['required' => false])
            ->add('stockage', ChoiceType::class, [
                'choices' => [
                    'AV (Avancement)' => 'AV',
                    'ST (Stockage)' => 'ST',
                    'UR (Urgent)' => 'UR',
                ],
            ])
           
            ->add('typeGalva', ChoiceType::class, [
                'choices' => [
                    'GB (Gros Bain)' => 'GB',
                    'PB (Petit Bain)' => 'PB',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonDeCommande::class,
        ]);
    }
}