<?php


namespace App\Form\Type\Admin;

use App\Entity\Edition;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

//use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class CustomEditionFilterType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

    }


    public function configureOptions(OptionsResolver $resolver) : void

    {
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'entity'],
            'value_type' => EntityType::class,
            'class' => Edition::class,
            ''
        ]);

    }

    public function getParent(): string
    {
        return EntityType::class;
    }


}
