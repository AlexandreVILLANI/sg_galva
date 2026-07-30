<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\DechargementUrgence;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DechargementUrgenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Sélectionner un client --',
                'required' => false,
                'attr' => ['class' => 'select2'],
            ])
            ->add('lignes', CollectionType::class, [
                'entry_type' => LigneDechargementUrgenceType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DechargementUrgence::class,
        ]);
    }
}
