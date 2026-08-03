<?php

namespace App\Form;

use App\Entity\BonTravail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CataphoreseBonTravailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isCataphoreseTermine', CheckboxType::class, [
                'label' => 'Travail de cataphorèse terminé',
                'required' => false,
            ])
            ->add('observationsCataphorese', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Ajoutez vos observations ici...'],
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
