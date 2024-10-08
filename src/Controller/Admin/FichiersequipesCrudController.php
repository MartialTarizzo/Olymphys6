<?php


namespace App\Controller\Admin;


use App\Controller\Admin\Filter\CustomEditionFilter;
use App\Controller\Admin\Filter\CustomEquipeFichierFilter;
use App\Controller\Admin\Filter\CustomEquipeFilter;
use App\Controller\Admin\Filter\CustomEquipespasseesFilter;

use App\Controller\Admin\Filter\CustomFichiersequipesFilter;
use App\Entity\Edition;
use App\Entity\Elevesinter;
use App\Entity\Equipesadmin;
use App\Entity\Fichiersequipes;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use App\Entity\Odpf\OdpfFichierspasses;
use App\Entity\User;
use App\Service\OdpfRempliEquipesPassees;
use App\Service\valid_fichiers;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Exception;

use setasign\Fpdi\PdfParser\CrossReference\AbstractReader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;
use ZipArchive;


class FichiersequipesCrudController extends AbstractCrudController
{
    private RequestStack $requestStack;
    private ValidatorInterface $validator;
    private AdminContextProvider $adminContextProvider;

    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $em;
    private ManagerRegistry $doctrine;
    private AdminUrlGenerator $adminUrlGenerator;


    public function __construct(RequestStack $requestStack, AdminContextProvider $adminContextProvider, ValidatorInterface $validator, EntityManagerInterface $entitymanager, ParameterBagInterface $parameterBag, ManagerRegistry $doctrine, AdminUrlGenerator $adminUrlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->adminContextProvider = $adminContextProvider;
        $this->validator = $validator;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->parameterBag = $parameterBag;
        $this->em = $entitymanager;
        $this->doctrine = $doctrine;


    }

    public static function getEntityFqcn(): string
    {
        return Fichiersequipes::class;
    }

    public function configureFilters(Filters $filters): Filters
    {

        $filters
            ->add(CustomEquipeFichierFilter::new('equipe', 'equipe'))
            ->add(CustomEditionFilter::new('edition', 'edition'));
        if ($this->requestStack->getCurrentRequest()->query->get('typefichier') <= 1) {
            $filters->
            add(CustomFichiersequipesFilter::new('typefichier', 'categorie'));
        }
        return $filters;
    }

    public function configureCrud(Crud $crud): Crud
    {

        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $session = $this->requestStack->getSession();
        $exp = new UnicodeString('<sup>e</sup>');

        $typefichier = $this->requestStack->getMainRequest()->query->get('typefichier');
        if ($typefichier !== null) {
            $this->requestStack->getSession()->set('typefichier', $typefichier);
        }
        if ($typefichier == null) {
            $typefichier = $this->requestStack->getSession()->get('typefichier');
            if (isset($_REQUEST['entityId'])) { // dans le cas d'un affichage du fichier autorisation à partir d'un élèveinter
                $typefichier = $this->doctrine->getRepository(Fichiersequipes::class)->findOneBy(['id' => $_REQUEST['entityId']])->getTypefichier();
            }

        }

        $concours = $this->requestStack->getMainRequest()->query->get('concours');
        if ($concours !== null) {
            $this->requestStack->getSession()->set('concours', $concours);
        }
        if ($concours == null) {
            if ($concours == null) {
                $concours = $this->requestStack->getSession()->get('concours');
            }
            if ($typefichier == 6) {
                $concours = 0;
            }
        }
        $pageName = $this->requestStack->getMainRequest()->query->get('crudAction');
        $edition = $session->get('edition');
        if (new DateTime('now') < $session->get('edition')->getDateouverturesite()) {
            $edition = $repositoryEdition->findOneBy(['ed' => $edition->getEd() - 1]);
        }
        if (isset($_REQUEST['filters']['equipe'])) {
            $equipeId = $_REQUEST['filters']['equipe'];
            $equipe = $this->doctrine->getManager()->getRepository(Equipesadmin::class)->findOneBy(['id' => $equipeId]);

        }
        if (isset($_REQUEST['filters']['edition'])) {
            $idEdition = $_REQUEST['filters']['edition'];
            $edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['id' => $idEdition]);
            $session->set('titreedition', $edition);
        }
        if (isset($_REQUEST['filters']['typefichier'])) {

            //$edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['id' => $idEdition]);
            $session->set('typefichier', $typefichier);
        }

        $concours == 1 ? $concourslit = 'national' : $concourslit = 'interacadémique';
        $concoursmemoire = $concourslit;
        if (($concoursmemoire == 'interacadémique') and (new DateTime('now') > $edition->getConcoursCia())) {
            $concoursmemoire = $concourslit . '(équipes non sélectionnées)';
        }

        if ($pageName == 'index') {
            if (($typefichier == 0) | ($typefichier == 2)) {
                //dump($typefichier);
                //dump($edition->getEd());
                //dd('Les ' . $this->getParameter('type_fichier_lit')[$typefichier] . 's de la ' . $edition->getEd() . $exp . ' édition');
                $crud = $crud->setPageTitle('index', 'Les ' . $this->getParameter('type_fichier_lit')[$typefichier] . 's de la ' . $edition->getEd() . $exp . ' édition. Concours ' . $concoursmemoire);
            }

            if ($typefichier == 3) {
                $crud = $crud->setPageTitle('index', 'Les diaporamas(concours national) de la ' . $edition->getEd() . $exp . ' édition');
            }
            if (($typefichier == 4) or ($typefichier == 8)) {
                $crud = $crud->setPageTitle('index', 'Les fiches sécurité de la ' . $edition->getEd() . $exp . ' édition du concours ' . $concourslit);
            }
            if ($typefichier == 5) {
                $crud = $crud->setPageTitle('index', 'Les diaporamas(pour le concours interacadémique) de la ' . $edition->getEd() . $exp . ' édition');
            }
            if ($typefichier == 6) {
                $crud = $crud->setPageTitle('index', 'Les autorisations photos de la ' . $edition->getEd() . $exp . ' édition');
            }
            if ($typefichier == 7) {
                $crud = $crud->setPageTitle('index', 'Les questionnaires de la ' . $edition->getEd() . $exp . ' édition');
            }
        }
        $crud->setPageTitle('new', 'Nouveau fichier')
            ->setPageTitle('edit', 'Modifier le fichier')
            ->setPageTitle('detail', 'Détail du fichier')
            ->showEntityActionsInlined();
        $_REQUEST['typefichier'] = $typefichier;
        $_REQUEST['concours'] = $concours;
        return $crud;
    }

    public function set_type_fichier($valueIndex, $valueSubIndex)
    {
        if ($valueIndex == 9) {
            switch ($valueSubIndex) {
                case 1 :
                    $typeFichier = 0; //mémoires ou annexes 1
                    break;
                case 2:
                    $typeFichier = 2;  //résumés
                    break;
                case 3 :
                    $typeFichier = 4; //Fiches sécurité
                    break;
                case 4 :
                    $typeFichier = 5; //Diaporamas interacadémiques
                    break;
                case 6 :
                    $typeFichier = 6; //Diaporamas interacadémiques
                    break;
                case 8:
                    $typeFichier = 7; //Questionnaires interacadémiques
                    break;
            }
        }
        if ($valueIndex == 10) {
            switch ($valueSubIndex) {
                case 3 :
                    $typeFichier = 0; //mémoires 0 ou annexes 1
                    break;
                case 4:
                    $typeFichier = 2;  //résumés
                    break;
                case 5 :
                    $typeFichier = 3; //Diaporama de la présentation nationale
                    break;
                case 8 :
                    $typeFichier = 4; //Fiches sécurités des équipes sélectionnées
                    break;
            }
        }
        return $typeFichier;
    }

    public function configureActions(Actions $actions): Actions
    {
        $equipeId = 'na';
        $editionId = 'na';
        $concours = 0;
        if (isset($_REQUEST['concours'])) {
            $concours = $_REQUEST['concours'];

            $concours == 0 ? $national = false : $national = true;
        }
        $typefichierfiltre = 'na';
        if (isset($_REQUEST['filters'])) {

            if (isset($_REQUEST['filters']['equipe'])) {
                $equipeId = $_REQUEST['filters']['equipe'];

            }
            if (isset($_REQUEST['filters']['edition'])) {
                $editionId = $_REQUEST['filters']['edition'];

            }
            if (isset($_REQUEST['filters']['typefichier'])) {
                $typefichierfiltre = $_REQUEST['filters']['typefichier'];

            }
        }

        if (isset($_REQUEST['typefichier'])) {
            $typefichier = $_REQUEST['typefichier'];
        } elseif (isset($_REQUEST['entityId'])) {// dans le cas d'un affichage du fichier autorisation à partir d'un élèveinter
            $idfichier = $_REQUEST['entityId'];
            $typefichier = $this->doctrine->getRepository(Fichiersequipes::class)->findOneBy(['id' => $idfichier])->getTypefichier();

        } else {
            $typefichier = $this->requestStack->getSession()->get('typefichier');

        }

        $telechargerFichiers = Action::new('telecharger', 'Télécharger  les fichiers', 'fa fa-file-download')
            ->linkToRoute('telechargerFichiers', ['ideditionequipe' => $editionId . '-' . $equipeId . '-' . $concours . '-' . $typefichier . '-' . $typefichierfiltre])
            ->createAsGlobalAction();
        //->displayAsButton()            ->setCssClass('btn btn-primary');;
        $telechargerUnFichier = Action::new('telechargerunfichier', 'Télécharger le fichier', 'fa fa-file-download')
            ->linkToRoute('telechargerUnFichier', function (Fichiersequipes $fichier): array {
                return [
                    'idEntity' => $fichier->getId(),

                ];
            });

        $newFichier = Action::new('deposer', 'Déposer un fichier')->linkToCrudAction('new')->setHtmlAttributes(['typefichier' => $this->requestStack->getCurrentRequest()->query->get('typefichier')])->createAsGlobalAction();
        $actions = $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX, 'Retour à la liste')
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Déposer le fichier')->setHtmlAttributes(['typefichier' => $this->requestStack->getCurrentRequest()->getSession()->get('typefichier')]);
            })
            ->update(Crud::PAGE_NEW, Action::INDEX, function (Action $action) {
                return $action->setLabel('Retour à la liste')->setHtmlAttributes(['typefichier' => $this->requestStack->getCurrentRequest()->getSession()->get('typefichier')]);
            })
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->add(Crud::PAGE_INDEX, $telechargerFichiers)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {

                return $action->setLabel('Nouveau fichier')->setHtmlAttributes(['typefichier' => $this->requestStack->getCurrentRequest()->getSession()->get('typefichier')]);
            })
            ->add(Crud::PAGE_INDEX, $telechargerUnFichier)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);

        return $actions;
    }


    #[Route("/Admin/FichiersequipesCrud/telechargerFichiers,{ideditionequipe}", name: "telechargerFichiers")]
    public function telechargerFichiers(AdminContext $context, $ideditionequipe)
    {
        $session = $this->requestStack->getSession();
        $typefichier = $this->requestStack->getSession()->get('typefichier');
        $typefichierfiltre = explode('-', $ideditionequipe)[4];

        if ($typefichierfiltre != 'na') {
            $typefichier = $typefichierfiltre;
        }
        $repositoryEquipe = $this->doctrine->getRepository(Equipesadmin::class);
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $idEdition = explode('-', $ideditionequipe)[0];
        $idEquipe = explode('-', $ideditionequipe)[1];
        $concours = explode('-', $ideditionequipe)[2];

        $concours == '0' ? $national = false : $national = true;
        $concours == '0' ? $selectionnee = false : $selectionnee = true;
        $qb = $this->doctrine->getManager()->getRepository(Fichiersequipes::class)->CreateQueryBuilder('f');
        $qb->leftJoin('f.equipe', 'eq');

        if ($concours == 1 and $typefichier != 6) {
            $qb->andWhere('eq.selectionnee =:selectionnee')
                ->setParameter('selectionnee', $selectionnee);

        }

        if ($typefichierfiltre == 'na') {
            if ($typefichier == 0) {
                $qb->andWhere('f.typefichier <= 1')
                    ->andWhere('f.national =:national')
                    ->setParameter('national', $national);

            }
            if ($typefichier > 1) {
                if ($typefichier != 4) {
                    $qb->andWhere('f.typefichier =:typefichier')
                        ->andWhere('f.national =:national')
                        ->setParameter('typefichier', $typefichier)
                        ->setParameter('national', $national);
                }
                if ($typefichier == 4) {
                    $qb->andWhere('f.typefichier=:typefichier OR f.typefichier =:valeur')
                        ->setParameter('valeur', 8)
                        ->setParameter('typefichier', $typefichier);

                }
                if ($typefichier != 6 and $typefichier != 4) {
                    $qb->andWhere('f.national =:concours')
                        ->setParameter('concours', $concours);
                }

            }
        }
        if ($typefichierfiltre != 'na') {
            if ($typefichier == 0) {
                $qb->andWhere('f.typefichier <= 1')
                    ->setParameter('typefichier', $typefichier);;
            }
            if ($typefichier > 1) {

                $qb->andWhere('f.typefichier =:typefichier')
                    ->setParameter('typefichier', $typefichier);

                if ($typefichier == 4) {
                    $qb->andWhere('f.typefichier =:typefichier OR f.typefichier =:valeur')
                        ->setParameter('valeur', 8)
                        ->setParameter('typefichier', $typefichier);

                }
            }
        }
        if ($idEdition == 'na') {
            $editionId = $session->get('edition')->getId();
            $edition = $repositoryEdition->find($editionId);
            if (new DateTime('now') < $session->get('edition')->getDateouverturesite()) {
                $edition = $repositoryEdition->findOneBy(['ed' => $edition->getEd() - 1]);
            }
        } else {
            $edition = $repositoryEdition->findOneBy(['id' => $idEdition]);

        }
        if ($this->requestStack->getSession()->get('concours') == 1) {

            $qb->addOrderBy('eq.lettre', 'ASC');

        }
        if ($idEquipe != 'na') {
            $equipe = $repositoryEquipe->findOneBy(['id' => $idEquipe]);
            $edition = $equipe->getEdition();
            $qb->andWhere('f.equipe =:equipe')
                ->setParameter('equipe', $equipe);

        }
        $qb->andWhere('f.edition =:edition')
            ->setParameter('edition', $edition);

        $fichiers = $qb->getQuery()->getResult();

        if ($fichiers != []) {
            $zipFile = new ZipArchive();
            $now = new DateTime();
            $fileNameZip = 'Telechargement_olymphys_' . $this->getParameter('type_fichier')[$typefichier] . '_' . $now->format('d-m-Y\-His');

            if (($zipFile->open($fileNameZip, ZipArchive::CREATE) === TRUE) and (null !== $fichiers)) {

                foreach ($fichiers as $fichier) {
                    try {
                        if ($fichier->getTypefichier() < 4) {
                            $fichier->getPublie() == true ? $autorise = 'publie/' : $autorise = 'prive/';
                            $fileName = $this->getParameter('app.path.odpf_archives') . '/' . $edition->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$typefichier] . '/' . $autorise . $fichier->getFichier();
                        } else {
                            $fileName = $this->getParameter('app.path.odpf_archives') . '/' . $edition->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$typefichier] . '/' . $fichier->getFichier();
                        }
                        $zipFile->addFromString(basename($fileName), file_get_contents($fileName));//voir https://stackoverflow.com/questions/20268025/symfony2-create-and-download-zip-file

                    } catch (Exception $e) {

                    }

                }

                $zipFile->close();


            }

            $response = new Response(file_get_contents($fileNameZip));//voir https://stackoverflow.com/questions/20268025/symfony2-create-and-download-zip-file

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $fileNameZip
            );
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', $disposition);

            @unlink($fileNameZip);
            return $response;
        }
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(FichiersequipesCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Route("/Admin/FichiersequipesCrud/telechargerUnFichier", name: "telechargerUnFichier")]
    public function telechargerUnFichier(AdminContext $context)
    {
        if (isset($_REQUEST['routeParams'])) {
            $idFichier = $_REQUEST['routeParams']['idEntity'];
        }
        if (isset($_REQUEST['entityId'])) {
            $idFichier = $_REQUEST['entityId'];

        }
        $fichier = $this->doctrine->getRepository(Fichiersequipes::class)->findOneBy(['id' => $idFichier]);
        $edition = $fichier->getEdition();
        $typefichier = $fichier->getTypefichier();
        $chemintypefichier = $this->getParameter('type_fichier')[$typefichier] . '/';
        if ($typefichier == 1) {
            $chemintypefichier = $this->getParameter('type_fichier')[0] . '/';
        }
        if ($typefichier < 4) {
            $fichier->getPublie() == true ? $acces = $this->getParameter('fichier_acces')[1] : $acces = $this->getParameter('fichier_acces')[0];
            $chemintypefichier = $chemintypefichier . '/' . $acces . '/';
        }

        $file = $this->getParameter('app.path.odpf_archives') . '/' . $edition->getEd() . '/fichiers/' . $chemintypefichier . $fichier->getFichier();

        $response = new Response(file_get_contents($file));

        $type = mime_content_type($file);
        if (str_contains($_SERVER['HTTP_USER_AGENT'], 'iPad') or str_contains($_SERVER['HTTP_USER_AGENT'], 'Mac OS X')) {
            $response = new BinaryFileResponse($file);

        }
        $response->headers->set('Content-Disposition: attachment', 'attachment; filename="' . $fichier->getFichier() . '"');
        $response->headers->set('Content-Description', 'File Transfer');
        $response->headers->set('Content-type', $type);
        $response->headers->set('Content-Length', filesize($file));

        return $response;


    }

    public function configureFields(string $pageName): iterable

    {

        $repositoryEdition = $this->doctrine->getRepository(Edition::class);

        $idEdition = $this->requestStack->getSession()->get('edition')->getId();

        $edition = $repositoryEdition->findOneBy(['id' => $idEdition]);

        if (new DateTime('now') < $this->requestStack->getSession()->get('edition')->getDateouverturesite()) {
            $edition = $repositoryEdition->findOneBy(['ed' => $edition->getEd() - 1]);//Pour que les données du concours N s'affichent tant qu'on a pas ouvert les inscriptions du N+1
        }
        $numtypefichier = $_REQUEST['typefichier'];
        if ($numtypefichier != null) {
            $this->requestStack->getCurrentRequest()->query->set('typefichier', $numtypefichier);
        } else {
            $numtypefichier = $this->requestStack->getSession()->get('typefichier');
        }
        $concours = $_REQUEST['concours'];

        if ($pageName == Crud::PAGE_NEW) {


            if (($numtypefichier != 6) or ($numtypefichier != 4)) {//  si le fichier n'est pas une autorisation photo ou une fichesécurité'
                $panel1 = FormField::addPanel('<p style= "color :red" > Déposer un nouveau ' . $this->getParameter('type_fichier_lit')[$numtypefichier] . '  </p> ');
            }
            if ($numtypefichier == 6) {//Si le fichier est une autorisation photo
                $panel1 = FormField::addPanel('<p style= "color :red" > Déposer  une nouvelle autorisation photos  </p> ');
            }
            if (($numtypefichier == 4) or ($numtypefichier == 8)) {//si le fichier est une fiche sécurité exposé ou exposition
                $panel1 = FormField::addPanel('<p style= "color :red" > Déposer  une nouvelle fiche sécurité  </p> ');

            }

        }


        if ($pageName == Crud::PAGE_EDIT) {

            $panel1 = FormField::addPanel('<p style= "color:red" > Editer le fichier ' . $this->getParameter('type_fichier_lit')[$this->requestStack->getSession()->get('typefichier')] . '  </p> ');
            $numtypefichier = $this->requestStack->getSession()->get('typefichier');

        }
        $listeEquipes = $this->doctrine->getRepository(Equipesadmin::class)->createQueryBuilder('eq')
            ->select()->andWhere('eq.edition =:edition')
            ->andWhere('eq.selectionnee =:selectionnee ')
            ->andWhere('eq.numero <:value')
            ->setParameter('value', 100)
            ->setParameter('edition', $edition)
            ->setParameter('selectionnee', $this->requestStack->getSession()->get('concours'))
            ->addOrderBy('eq.edition', 'DESC')
            ->addOrderBy('eq.lettre', 'ASC')
            ->addOrderBy('eq.numero', 'ASC')
            ->getQuery()->getResult();
        $equipe = AssociationField::new('equipe')
            ->setFormTypeOptions([
                'choices' => $listeEquipes]);
        $fichierFile = Field::new('fichierFile', 'fichier')//Le champ de chargement du fichier
        ->setFormType(VichFileType::class)
            ->setLabel('Fichier')
            ->onlyOnForms()
            ->setFormTypeOption('allow_delete', false);//sinon la case à cocher delete s'affiche
        //$numtypefichier=$this->set_type_fichier($_REQUEST['menuIndex'],$_REQUEST['submenuIndex']);
        switch ($numtypefichier) {
            case 0 :
                $article = 'le';
                break;
            case 1 :
                $article = 'l\'';
                break;
            case 2 :
                $article = 'le';
                break;
            case 3 :
                $article = 'le';
                break;
            case 4 :
                $article = 'la';
                break;
            case 5 :
                $article = 'le';
                break;
            case 6 :
                $article = 'l\'';
                break;
            case 7 :
                $article = 'le';
                break;
            case 8 :
                $article = 'la';
                break;
        }

        $panel2 = FormField::addPanel('<p style="color:red" > Modifier ' . $article . ' ' . $this->getParameter('type_fichier_lit')[$_REQUEST['typefichier']] . '</p> ');
        $id = IntegerField::new('id', 'ID');
        $fichier = TextField::new('fichier')->setTemplatePath('bundles\\EasyAdminBundle\\liste_fichiers.html.twig');
        $publie = BooleanField::new('publie')->renderAsSwitch(false);//le basculement du bouton ne permet pas le transfert des fichiers du dossier prive vers le dossier publie et inversement, l'update complet est nécessaire
        $typefichier = IntegerField::new('typefichier');
        if ($pageName == Crud::PAGE_INDEX) {
            $context = $this->adminContextProvider->getContext();
            $context->getRequest()->query->set('concours', $_REQUEST['concours']);
            $context->getRequest()->query->set('typefichier', $_REQUEST['typefichier']);
        }
        $annexe = ChoiceField::new('typefichier', 'Mémoire ou annexe')
            ->setChoices(['Memoire' => 0, 'Annexe' => 1])
            ->setFormTypeOptions(['required' => true])
            ->setColumns('col-sm-4 col-lg-3 col-xxl-2');
        $national = BooleanField::new('national');
        $updatedAt = DateTimeField::new('updatedAt')->setSortable(true);
        $nomautorisation = TextField::new('nomautorisation', 'NOM')->setSortable(true);
        $editionField = AssociationField::new('edition', 'Edition');
        $listeEleves = $this->doctrine->getRepository(Elevesinter::class)->createQueryBuilder('el')
            ->leftJoin('el.equipe', 'eq')
            ->where('eq.edition =:edition')
            ->setParameter('edition', $edition)
            ->addOrderBy('eq.numero', 'ASC')
            ->getQuery()->getResult();
        $qb = $this->doctrine->getRepository(User::class)->createQueryBuilder('u');
        $listeEquipes=$this->doctrine->getRepository(Equipesadmin::class)->findBy(['edition'=>$this->requestStack->getSession()->get('edition')]);
        $listeProfs=[];
        $i=0;
            foreach($listeEquipes as $equipe){
                $listeProfs[$i]=$equipe->getIdProf1();
                if($equipe->getIdProf2()!=null){
                    $i++;
                    $listeProfs[$i]=$equipe->getIdProf2();
                }
            }
        /*$listeProfs = $this->doctrine->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('u.roles =:roles')
            ->setParameter('roles', 'a:2:{i:0;s:9:"ROLE_PROF";i:2;s:9:"ROLE_USER";}')

            // ->orWhere($qb->expr()->like('entity.roles',':roles'))
            // ->setParameter('roles','%i:0;s:9:"ROLE_PROF";i:2;s:9:"ROLE_USER";%')
            ->addOrderBy('u.nom', 'ASC')
            ->getQuery()->getResult();*/

        $eleve = AssociationField::new('eleve')
            ->setFormTypeOptions(['class' => Elevesinter::class,
                'choices' => $listeEleves,
                'choice_label' => 'getNomPrenom',
                'mapped' => true,
                'required' => false]);
        $prof = AssociationField::new('prof')
            ->setFormTypeOptions(['class' => User::class,
                'choices' => $listeProfs,
                'choice_label' => 'getNomPrenom',
                'mapped' => true,
                'required' => false]);


        $editionEd = TextareaField::new('edition', 'Edition');
        $equipelibel = AssociationField::new('equipe', 'Equipe')->setSortable(true);
        if ($numtypefichier != 6) {
            $equipeNumero = IntegerField::new('equipe.numero', 'numero')->setSortable(true);
            $equipeLettre = TextField::new('equipe.lettre', 'Lettre equipe')->setSortable(true);
            $equipeTitreProjet = TextField::new('equipe.titreProjet', 'Projet')->setSortable(true);
        };
        $updatedat = DateTimeField::new('updatedat', 'Déposé le ')->setSortable(true);

        if (Crud::PAGE_INDEX === $pageName) {
            $_REQUEST['typefichier'] = $numtypefichier;
            if ($numtypefichier == 6) {
                return [$editionEd, $equipelibel, $fichier, $updatedat];
            } else {
                return [$editionEd, $equipeNumero, $equipeLettre, $equipeTitreProjet, $publie, $fichier, $updatedat]; //Le basculement du bouton publie ne tranfert pas le fichier, il faut appeler update
            }
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            if ($numtypefichier == 6) {
                return [$fichier, $nomautorisation, $typefichier, $updatedAt, $nomautorisation, $eleve, $prof];
            } else {
                return [$fichier, $typefichier, $national, $publie, $updatedAt, $edition, $equipe];
            }
        }
        if (Crud::PAGE_NEW === $pageName) {


            if ($numtypefichier == 0) {
                return [$panel1, $equipe, $fichierFile, $publie, $annexe];
            }
            if (($numtypefichier == 2) or ($numtypefichier == 3) or ($numtypefichier == 4) or ($numtypefichier == 5)) {


               return [$panel1, $equipe, $fichierFile, $publie];

            }
            if ($numtypefichier == 6) {

                return [$panel1, $eleve, $prof, $fichierFile];
            }
        }
        if (Crud::PAGE_EDIT === $pageName) {
            if ($numtypefichier == 0) {
                return [$panel1, $equipe, $fichier, $fichierFile, $annexe, $national, $publie];
            }
            if (($numtypefichier == 2) or ($numtypefichier == 3) or ($numtypefichier == 4) or ($numtypefichier == 5)) {
                return [$panel1, $equipe, $publie, $fichierFile,$national];
            }
            if ($numtypefichier == 6) {

                return [$panel1, $equipe, $eleve, $prof, $fichierFile];
            }
        }

    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $repositoryEquipe = $this->doctrine->getRepository(Equipesadmin::class);
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $repositoryFichiers = $this->doctrine->getRepository(Fichiersequipes::class);
        $qb = $repositoryFichiers->createQueryBuilder('entity');
        $qb->leftJoin('entity.equipe', 'eq');
        $session = $this->requestStack->getSession();
        $context = $this->adminContextProvider->getContext();
        $typefichier = $context->getRequest()->query->get('typefichier');

        $concours = $context->getRequest()->query->get('concours');

        $concours == '0' ? $national = 0 : $national = 1;
        $concours == '0' ? $selectionnee = false : $selectionnee = true;
        $edition = $repositoryEdition->find($this->requestStack->getSession()->get('edition')->getId());
        $qb->andWhere('entity.edition =:edition');
        if (new DateTime('now') < $this->requestStack->getSession()->get('edition')->getDateouverturesite()) {
            $edition = $repositoryEdition->findOneBy(['ed' => $edition->getEd() - 1]);
        }
        if (!isset($_REQUEST['filters'])) {
            //$qb->andWhere('entity.typefichier =:typefichier');
            $qb->setParameter('edition', $edition);
            if ($typefichier == null) {
                $typefichier = $this->requestStack->getSession()->get('typefichier');
            }
            if ($typefichier == 0) {
                $qb->andWhere('entity.typefichier <=:typefichier');
                $typefichier = $typefichier + 1;
            }
            if ($typefichier > 1) {
                if ($typefichier != 4) {
                    $qb->andWhere('entity.typefichier=:typefichier');
                }

                if ($typefichier == 4) {
                    $qb->andWhere('entity.typefichier=:typefichier OR entity.typefichier=:valeur')
                        ->setParameter('valeur', 8);
                }
            }
            $qb->setParameter('typefichier', $typefichier);

            if ($concours == 1 and $typefichier != 6) {
                $qb->andWhere('eq.selectionnee =:selectionnee')
                    ->setParameter('selectionnee', $selectionnee);
            }
            if ($typefichier != 6) {
                if ($typefichier != 4) {
                    if ($typefichier != 8) {
                        $qb->andWhere('entity.national =:concours')
                            ->setParameter('concours', $concours);

                    }
                }
            }


        }
        if (isset($_REQUEST['filters'])) {
            if (isset($_REQUEST['filters']['equipe'])) {
                $idEquipe = $_REQUEST['filters']['equipe'];
                $equipe = $repositoryEquipe->find($idEquipe);
                if ($typefichier == 0) {
                    $qb->andWhere('entity.typefichier <=:typefichier');
                    $typefichier = $typefichier + 1;
                }
                if ($typefichier > 1) {
                    if ($typefichier != 4) {
                        $qb->andWhere('entity.typefichier=:typefichier');
                    }

                    if ($typefichier == 4) {
                        $qb->andWhere('entity.typefichier=:typefichier OR entity.typefichier=:valeur')
                            ->setParameter('valeur', 8);
                    }
                }
                $qb->andWhere('entity.equipe =:equipe')
                    ->setParameter('equipe', $equipe)
                    ->setParameter('edition', $equipe->getEdition())
                    ->setParameter('typefichier', $typefichier);
            } else {
                if (!isset($_REQUEST['filters']['edition'])) {
                    $qb->setParameter('edition', $edition);

                } else {
                    $idEdition = $_REQUEST['filters']['edition'];
                    $edition = $repositoryEdition->findOneBy(['id' => $idEdition]);
                    $session->set('titreedition', $edition);
                    $qb->setParameter('edition', $edition);
                }
                if (isset($_REQUEST['filters']['typefichier'])) {
                    $typefichier = $_REQUEST['filters']['typefichier'];
                }

                if ($typefichier == 0) {
                    $qb->andWhere('entity.typefichier <=:typefichier');
                    $typefichier = $typefichier + 1;
                }
                if ($typefichier > 1) {
                    if ($typefichier != 4) {
                        $qb->andWhere('entity.typefichier=:typefichier');
                    }

                    if ($typefichier == 4) {
                        $qb->andWhere('entity.typefichier=:typefichier OR entity.typefichier=:valeur')
                            ->setParameter('valeur', 8);
                    }
                }

                $qb->setParameter('typefichier', $typefichier);

                if ($concours == 1 and $typefichier != 6) {
                    $qb->andWhere('eq.selectionnee =:selectionnee')
                        ->setParameter('selectionnee', $selectionnee);
                }
                if ($typefichier != 6) {
                    if ($typefichier != 4) {
                        if ($typefichier != 8) {
                            $qb->andWhere('entity.national =:concours')
                                ->setParameter('concours', $concours);

                        }
                    }
                }
            }
        }


        if (isset($_REQUEST['sort'])) {
            $qb->resetDQLPart('orderBy');;
            $sort = $_REQUEST['sort'];
            if (key($sort) == 'equipe.lettre') {
                $qb->addOrderBy('eq.lettre', $sort['equipe.lettre']);
            }
            if (key($sort) == 'equipe.numero') {
                $qb->addOrderBy('eq.numero', $sort['equipe.numero']);
            }
            if (key($sort) == 'fichier') {
                $qb->addOrderBy('entity.fichier', $sort['fichier']);
            }

            if (key($sort) == 'equipe.titreProjet') {
                $qb->addOrderBy('eq.titreProjet', $sort['equipe.titreProjet']);
            }
        } else {
            if ($concours == 0) {
                $qb->addOrderBy('eq.numero', 'ASC');
            }
            if ($concours == 1) {
                $qb->addOrderBy('eq.lettre', 'ASC');
            }
        }

        $session->set('titreedition', $this->getParameter('type_fichier_lit')[$typefichier]);


        return $qb;
    }


    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {   //Nécessaire pour que les fichiers déjà existants d'une équipe soient écrasés, non pas ajoutés
        //$validator = new valid_fichiers($this->validator, );



        $session = $this->requestStack->getSession();
        $dateconect = new DateTime('now');
        $equipe = $entityInstance->getEquipe();
        $repositoryFichiers = $this->doctrine->getRepository(Fichiersequipes::class);
        $ErrorMessage = $session->get('messageeasy');

        /*if ($ErrorMessage['text'] != '') {

            $session->set('messageeasy', ['text' => '']);
        } else {*/
        if ($entityInstance->getTypefichier() != 6) {
            $oldfichier = $repositoryFichiers->createQueryBuilder('f')
                ->where('f.equipe =:equipe')
                ->setParameter('equipe', $equipe)
                ->andWhere('f.typefichier =:typefichier')
                ->setParameter('typefichier', $entityInstance->getTypefichier())->getQuery()->getOneOrNUllResult();
        }
        if ($entityInstance->getTypefichier() == 6) {
            $oldfichier = $repositoryFichiers->createQueryBuilder('f')
                ->where('f.equipe =:equipe')
                ->setParameter('equipe', $equipe)
                ->andWhere('f.prof  =:prof or f.eleve=:eleve')
                ->setParameter('prof', $entityInstance->getProf())
                ->setParameter('eleve', $entityInstance->getEleve())
                ->getQuery()->getOneOrNUllResult();
        }
        if (null !== $oldfichier) {

            $oldfichier->setFichierFile($entityInstance->getFichierFile());
            if (($entityInstance->getTypefichier() < 4) and ($entityInstance->getNational() == true)) {
                $this->fichierspublies($entityInstance);
            }
            parent::updateEntity($entityManager, $oldfichier);
        } else {
            if ($this->requestStack->getSession()->get('concours') == 0) {
                $entityInstance->setNational(0);
            }
            if ($this->requestStack->getSession()->get('concours') == 1) {
                $entityInstance->setNational(1);
            }

        }
        if (($entityInstance->getTypefichier() < 4) and ($entityInstance->getNational() == true)) {
            $this->fichierspublies($entityInstance);//vérifie si le fichier est dans le bon sous dossier et le déplace sinon
            parent::updateEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
        }

    }


    /**
     * @throws NonUniqueResultException
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {   //Nécessaire pour que les fichiers déjà existants d'une équipe soient écrasés, non pas ajoutés

        $concours = $_REQUEST['concours'];
        $concours == '0' ? $concours = 0 : $concours = 1;
        $session = $this->requestStack->getSession();
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $editionId = $session->get('edition')->getId();

        //$edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['id' => $editionId]);
       // $edition = $entityInstance->getEquipe()->getEdition();
      //  $entityInstance->setEdition($edition);
        $validator = new valid_fichiers($this->validator, $this->parameterBag, $this->requestStack);
        $dateconect = new DateTime('now');
        $equipe = $entityInstance->getEquipe();
        $repositoryFichiers = $this->doctrine->getRepository(Fichiersequipes::class);

        /*
        $pos = strpos($_REQUEST['referrer'], 'typefichier');
        $typefichier = substr($_REQUEST['referrer'], $pos + 12, 5);*/

        $typefichier=$_REQUEST['typefichier'];
        if ($typefichier == 0) {//contrairement aux autres fichiers, le formulaire comporte un champ de choix memoire ou annexe
            $typefichier = $entityInstance->getTypefichier();
        }
        $entityInstance->setTypefichier($typefichier);


        //$entityInstance->setEdition($edition);
        $ErrorMessage = $session->get('easymessage');
        if ($ErrorMessage != null) {
            $this->addFlash('alert', $ErrorMessage);
            $this->redirectToRoute('admin', $_REQUEST);
        } else {
            if ($typefichier != 6) {
                $edition = $entityInstance->getEquipe()->getEdition();
                $entityInstance->setEdition($edition);
                $entityInstance->setNational($concours);
                $oldfichier = $repositoryFichiers->createQueryBuilder('f')//Pour vérifier si l'équipe choisie n'a pas déposé ce fichier, le choix des équipes ne filtrant pas ces dernières
                ->where('f.equipe =:equipe')
                    ->setParameter('equipe', $equipe)
                    ->andWhere('f.typefichier =:typefichier')
                    ->setParameter('typefichier', $entityInstance->getTypefichier())->getQuery()->getOneOrNUllResult();

                if (null !== $oldfichier) {//Ce fichier existe déjà
                    $oldfichier->setFichierFile($entityInstance->getFichierFile());//On remplace le fichier par le nouveau
                    $oldfichier->setNational($concours);
                    $this->em->persist($oldfichier);
                    $this->em->flush();
                    //la création du fichier passé n'est pas à effectuer puisqu'il doit déjà exister, lié au fichier du même nom
                } else {

                    if ($_REQUEST['concours'] == 0) {
                        $entityInstance->setNational(0);
                    }
                    if ($_REQUEST['concours'] == 1) {
                        $entityInstance->setNational(1);
                    }
                    //$this->flashbag->addSuccess('Le fichier a bien été déposé');
                    $this->em->persist($entityInstance);
                    $this->em->flush();
                    $rempliEquipePassee = new OdpfRempliEquipesPassees($this->doctrine);
                    $rempliEquipePassee->RempliOdpfFichiersPasses($entityInstance);
                    if (($entityInstance->getTypefichier() < 4) and ($entityInstance->getNational() == true)) {
                        $this->fichierspublies($entityInstance);
                    }
                    parent::persistEntity($entityManager, $entityInstance);


                }


            }
            if ($typefichier == 6) {
                //if ($entityInstance->getUser()==null) $edition = $entityInstance->getEleve()->getEquipe()->getEdition();

                $entityInstance->setNational(0);
                $citoyen = $entityInstance->getProf();
                $quidam = 'Prof';
                if ($citoyen == null) {
                    $citoyen = $entityInstance->getEleve();
                    $edition = $entityInstance->getEleve()->getEquipe()->getEdition();
                    $entityInstance->setEdition($edition);
                    $entityInstance->setEquipe($citoyen->getEquipe());
                    $quidam = 'Eleve';
                }
                else{
                    $idEdition=$this->requestStack->getSession()->get('edition')->getId();
                    $edition=$this->doctrine->getRepository(Edition::class)->find($idEdition);
                    $entityInstance->setEdition($edition);
                }
                $citoyen = $this->em->merge($citoyen);
                $oldfichier = $repositoryFichiers->createQueryBuilder('f')
                    ->where('f.prof =:citoyen or f.eleve=:citoyen')
                    ->setParameter('citoyen', $citoyen)
                    ->andWhere('f.typefichier =:typefichier')
                    ->setParameter('typefichier', $entityInstance->getTypefichier())->getQuery()->getOneOrNUllResult();
                $entityInstance->setNomautorisation($citoyen->getNom() . '-' . $citoyen->getPrenom());
                if (null != $oldfichier) {

                    $citoyen->setAutorisationphotos(null);
                    $this->em->persist($citoyen);

                    if ($quidam == 'Prof') {

                        $oldfichier->setProf(null);
                        $entityInstance->setProf($citoyen);
                    } else {
                        $oldfichier->setEleve(null);
                        $entityInstance->setEleve($citoyen);
                    }
                    $entityInstance->setNomautorisation($citoyen->getNomPrenom());

                    $this->em->remove($oldfichier);
                    $this->em->flush();
                    $citoyen->setAutorisationphotos($entityInstance);
                    $this->em->persist($citoyen);
                    $this->em->flush();
                    parent::persistEntity($entityManager, $entityInstance);
                }
                parent::persistEntity($entityManager, $entityInstance);

            }

        }

    }
    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
            $url = $this->container->get(AdminUrlGenerator::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);

    }


    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getTypefichier() == 6) {
            if ($entityInstance->getProf() !== null) {
                $prof = $entityInstance->getProf();
                $prof->setAutorisationphotos(null);
                $this->doctrine->getManager()->persist($prof);
                $this->doctrine->getManager()->flush();
                $entityInstance->setProf(null);
                $this->doctrine->getManager()->persist($entityInstance);
                $this->doctrine->getManager()->flush();

            }
            if ($entityInstance->getEleve() !== null) {
                $eleve = $entityInstance->getEleve();
                $eleve->setAutorisationphotos(null);
                $this->doctrine->getManager()->persist($eleve);
                $this->doctrine->getManager()->flush();
                $entityInstance->setEleve(null);

                $this->doctrine->getManager()->persist($entityInstance);
                $this->doctrine->getManager()->flush();

            }
            $this->doctrine->getManager()->remove($entityInstance);
        }
        //Il faut aussi effacer le fichiers des éditions passées
        $editionpassee=$this->doctrine->getRepository(OdpfEditionsPassees::class)
            ->findOneBy(['edition'=>$entityInstance->getEdition()->getEd()]);
        $equipepassee=$this->doctrine->getRepository(OdpfEquipesPassees::class)
            ->findOneBy(['editionspassees'=>$editionpassee,'numero'=>$entityInstance->getEquipe()->getNumero()]);
        $fichierspasses=$this->doctrine->getRepository(OdpfFichierspasses::class)
            ->findOneBy(['equipepassee'=>$equipepassee,'typefichier'=>$entityInstance->getTypefichier()]);
        if($fichierspasses!=null) {
            $this->doctrine->getManager()->remove($fichierspasses);
            $this->doctrine->getManager()->flush();
        }

        parent::deleteEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }

    public function fichierspublies($fichier)
    {
        $publie = $fichier->getPublie();
        $fichierName = $fichier->getFichier();
        $fichier->getTypefichier() == 1 ? $numtypeFichier = 0 : $numtypeFichier = $fichier->getTypefichier();
        //dd('opdf/odpf-archives/' . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$fichier->getTypefichier()] . '/prive/' . $fichierName);
        $path = $this->getParameter('app.path.odpf_archives') . '/';
        if ($publie == true) {
            if (!file_exists($path . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$numtypeFichier] . '/publie/' . $fichierName)) {
                rename($path . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$numtypeFichier] . '/prive/' . $fichierName, $path . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$numtypeFichier] . '/publie/' . $fichierName);
            }

        }
        if (($publie == false) or ($publie == null)) {

            if (!file_exists($path . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$numtypeFichier] . '/prive/' . $fichierName)) {
                rename($path . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$numtypeFichier] . '/publie/' . $fichierName, $path . $fichier->getEdition()->getEd() . '/fichiers/' . $this->getParameter('type_fichier')[$numtypeFichier] . '/prive/' . $fichierName);
            }

        }

    }


}