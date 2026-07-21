<?php

namespace App\Form;

use App\Entity\BonDeCommande;

use App\Form\LigneDechargementType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;


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
                'required' => false, // Autorise à ne rien envoyer
                'placeholder' => 'Aucun forfait', // Ajoute une ligne vide/par défaut au début
                'attr' => ['class' => 'ghost-select']
            ])
            ->add('nomChauffeur', TextType::class, [
                'required' => false,
                'label' => 'Nom du chauffeur',
                'attr' => ['class' => 'ghost-input', 'placeholder' => '(DOMEZEL LEO)']
            ])
            // --- Le champ pour les photos spécifiques au Bon ---
            ->add('imageFiles', FileType::class, [
                'label' => 'Documents complémentaires (Photos/PDF)',
                'multiple' => true,
                'mapped' => false, 
                'required' => false,
                'attr' => ['accept' => 'image/*,application/pdf']
            ])
            ->add('isGalvanisation', CheckboxType::class, ['required' => false])
            ->add('isCataphorese', CheckboxType::class, ['required' => false])
            ->add('stockage', ChoiceType::class, [
                'choices' => [
                    'AV' => 'AV (Avancement)',
                    'ST' => 'ST (Stockage)',
                    'UR' => 'UR (Urgent)',
                ],
            ])
           
            ->add('typeGalva', ChoiceType::class, [
                'choices' => [
                    'GB' => 'GB (Grand Bain)',
                    'PB' => 'PB (Petit Bain)',
                    'Mixte' => 'Mixte (GB + PB)',
                ],
            ])

            ->add('lignes', CollectionType::class, [
                'entry_type' => LigneDechargementType::class,
                'property_path' => 'fiche.lignes', 
                'entry_options' => ['label' => false],
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
            ])
            
            ->add('commentaire', TextareaType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'rows' => 3, 
                    'placeholder' => 'Remarques éventuelles (ex: pièces fragiles, urgence...)',
                    'class' => 'paper-textarea'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonDeCommande::class,
        ]);
    }
}