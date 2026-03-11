<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- IDENTITÉ ---
            ->add('nom', TextType::class, ['label' => 'Raison Sociale (Intitulé)'])
            ->add('abrege', TextType::class, ['label' => 'Nom Abrégé', 'required' => false])
            ->add('refInterne', TextType::class, ['label' => 'Référence Interne (Numéro)', 'required' => false])
            
            // --- CONTACT ---
            ->add('contact', TextType::class, ['label' => 'Nom du Contact', 'required' => false])
            ->add('telephone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('fax', TextType::class, ['label' => 'Télécopie (Fax)', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'E-mail', 'required' => false])
            
            // --- ADRESSES ---
            ->add('adresse_facturation', TextType::class, ['label' => 'Adresse Facturation', 'required' => false])
            ->add('adresse_livraison', TextType::class, ['label' => 'Adresse Livraison', 'required' => false])
            ->add('codePostal', TextType::class, ['label' => 'Code Postal', 'required' => false])
            ->add('ville', TextType::class, ['label' => 'Ville', 'required' => false])
            ->add('pays', TextType::class, ['label' => 'Pays', 'required' => false, 'data' => 'FRANCE'])

            // --- ADMINISTRATIF & COMPTA ---
            ->add('siret', TextType::class, ['label' => 'N° SIRET', 'required' => false])
            ->add('tvaIntra', TextType::class, ['label' => 'N° TVA Intra', 'required' => false])
            ->add('categorieComptable', TextType::class, ['label' => 'Catégorie Comptable', 'required' => false])
            ->add('encoursAutorise', TextType::class, ['label' => 'Encours Autorisé (€)', 'required' => false])
            
            // --- ALERTES ---
            ->add('messageAlerte', TextareaType::class, [
                'label' => 'Message d\'Alerte (S\'affichera sur les fiches)',
                'required' => false,
                'attr' => ['rows' => 3]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}