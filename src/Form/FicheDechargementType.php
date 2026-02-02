<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\FicheDechargement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Count; // <-- Ajouté pour la limite de photos

class FicheDechargementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir un client',
                'attr' => ['class' => 'form-select']
            ])
            ->add('observations', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            /* C'est ici qu'on gère le tableau dynamique de paquets */
            ->add('lignes', CollectionType::class, [
                'entry_type' => LigneDechargementType::class, // <-- Vérifie bien que cet import est là haut !
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false, // Indispensable pour que addLigne() soit appelé
                'prototype' => true,
            ])
            /* Gestion des photos (multiples) */
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos (max 3)',
                'mapped' => false, 
                'multiple' => true,
                'required' => false,
                'constraints' => [
                    // On limite à 3 fichiers maximum au niveau de la validation
                    new Count(['max' => 3, 'maxMessage' => 'Vous ne pouvez pas envoyer plus de 3 photos']),
                    new All([
                        new File([
                            'maxSize' => '10M',
                            'mimeTypes' => ['image/jpeg', 'image/png'],
                            'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG)',
                        ])
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FicheDechargement::class,
        ]);
    }
}