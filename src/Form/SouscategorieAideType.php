<?php

namespace App\Form;

use App\Entity\AideEnLigne;

use App\Entity\SousCategorieAide;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Tag\A;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SouscategorieAideType extends AbstractType
{
    private ManagerRegistry $em;

    public function __construct(ManagerRegistry $em){

        $this->em=$em;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $souscategorie=$this->em->getRepository(SousCategorieAide::class)->findAll();
        $builder
            ->add('souscategorieAide', EntityType::class, [
                'class' => SousCategorieAide::class,
                'by_reference'=>false,
                'required'=>false,



            ]);
    }


    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'data_class' => AideEnLigne::class,
        ]);
    }


}
