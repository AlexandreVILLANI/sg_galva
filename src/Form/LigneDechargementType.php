<?php

namespace App\Form;

use App\Entity\Emplacement;
use App\Entity\LigneDechargement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType; // Import indispensable
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LigneDechargementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nbPaquets', IntegerType::class, [
                'attr' => ['class' => 'table-input input-paquets', 'placeholder' => 'Nombre', 'min' => 1]
            ])
            // --- Nouvelle colonne Description ---
            ->add('description', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'table-input', 'placeholder' => 'Ex: Colliers, Tubes...']
            ])
            // ------------------------------------
            ->add('emplacement', EntityType::class, [
                'class' => Emplacement::class,
                'choice_label' => 'nom',
                'attr' => ['class' => 'table-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LigneDechargement::class,
        ]);
    }
}