<?php

namespace App\Controller\Aide;

use App\Controller\OdpfAdmin\AdminCKEditorField;
use App\Entity\AideEnLigne;
use App\Entity\CategorieAide;
use App\Entity\SousCategorieAide;
use App\Repository\CategorieAideRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class AideEnLigneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AideEnLigne::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->setPageTitle('index', 'Articles de l\'aide en ligne')
            ->setPaginatorPageSize(100);
    }

        public function configureFields(string $pageName): iterable
    {
        return [
            yield TextField::new('titre', 'Titre'),
            yield AssociationField::new('categorie','Catégorie'),
            yield AssociationField::new('sousCategorie','Sous-catégorie'),
            yield AdminCKEditorField::new('texte'),
            yield ChoiceField::new('permission')->setChoices([
                'ROLE_SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',
                'ROLE_ADMIN' => 'ROLE_ADMIN',
                'ROLE_COMITE' => 'ROLE_COMITE',
                'ROLE_PROF' => 'ROLE_PROF',
                'ROLE_JURY' => 'ROLE_JURY',
                'ROLE_JURYCIA' => 'ROLE_JURYCIA',
            ])


        ];
    }
}
