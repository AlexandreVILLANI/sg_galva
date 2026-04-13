<?php

namespace App\Form;

use App\Entity\BonTravail;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType; 
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType; 
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BonTravailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero', TextType::class, [
                'label' => 'N° Bon de Travail',
                'attr' => ['readonly' => true, 'class' => 'refi-input-ghost']
            ])
            ->add('delaiClient', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'ghost-input']
            ])
            ->add('exigenceParticuliere', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'ghost-input full-width']
            ])
            ->add('repriseUsinage', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'ghost-input full-width']
            ])
            ->add('observations', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'w-100', 'rows' => 3]
            ])
            // --- AJOUT ICI : Le champ pour la demande de certificat ---
            ->add('demandeCertificat', CheckboxType::class, [
                'label'    => 'Demande de certificat',
                'required' => false, 
                // Optionnel : tu peux ajouter une classe si tu as un style Bootstrap ou Tailwind
                // 'attr' => ['class' => 'form-check-input'] 
            ])
            
            // --- C'EST ICI QUE LA MAGIE OPÈRE ---
            ->add('lignes', CollectionType::class, [
                'entry_type' => LigneTravailType::class,
                'entry_options' => ['label' => false],
                'allow_add' => false, // On ne rajoute pas de lignes, elles existent déjà
                'allow_delete' => false,
                'by_reference' => true, // Important pour modifier les objets existants
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BonTravail::class,
        ]);
    }
}