<?php

namespace App\Form;

use App\Entity\Equipes;
use App\Entity\RecommandationsJuryCN;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecommandationsCnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pathpluginsWordcount = '../public/bundles/fosckeditor/plugins/wordcount/'; // with trailing slash sur le site
        if (str_contains('127.0.0.1', $_SERVER['SERVER_NAME'])) {
            $pathpluginsWordcount = 'bundles/fosckeditor/plugins/wordcount/';// with trailing slash en local
        }
        $builder
            ->add('texte', CKEditorType::class,[

                'label' => 'maximum 1500 caractères',
                'required' => false,
                'config' => array(
                    'extraPlugins' => 'wordcount',),
                'plugins' => array(
                    'wordcount' => array(
                        'path' => $pathpluginsWordcount,
                        'filename' => 'plugin.js',
                    ))

            ])
            ->add('Enregistrer', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'data_class' => RecommandationsJuryCN::class,
        ]);
    }
}
