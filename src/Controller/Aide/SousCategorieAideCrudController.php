<?php

namespace App\Controller\Aide;

use App\Entity\AideEnLigne;
use App\Entity\SousCategorieAide;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;


class SousCategorieAideCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SousCategorieAide::class;
    }
    public function configureCrud(Crud $crud): Crud    {
        return $crud->setPageTitle('index','Sous-catégories de l\'aide');

    }
}
