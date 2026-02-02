<?php

namespace App\Form;

use App\Entity\Emplacement;
use App\Entity\LigneDechargement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LigneDechargementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nbPaquets', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre']
            ])
            ->add('emplacement', EntityType::class, [
                'class' => Emplacement::class,
                'choice_label' => 'nom',
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LigneDechargement::class,
        ]);
    }
}