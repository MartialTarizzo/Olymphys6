<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\CustomCentreFilter;
use App\Controller\Admin\Filter\CustomEditionFilter;
use App\Entity\Centrescia;
use App\Entity\Edition;
use App\Entity\Elevesinter;
use App\Entity\Equipesadmin;
use App\Entity\Fichiersequipes;
use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Professeurs;
use App\Entity\User;
use App\Form\Type\Admin\CustomCentreFilterType;
use App\Form\Type\CentreType;
use App\Service\Maj_profsequipes;
use App\Service\OdpfRempliEquipesPassees;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;

class EquipesadminCrudController extends AbstractCrudController

{
    private RequestStack $requestStack;
    private AdminContextProvider $adminContextProvider;
    private ManagerRegistry $doctrine;

    public function __construct(RequestStack $requestStack, AdminContextProvider $adminContextProvider, ManagerRegistry $doctrine)
    {
        $this->requestStack = $requestStack;;
        $this->adminContextProvider = $adminContextProvider;
        $this->doctrine = $doctrine;
    }

    public static function getEntityFqcn(): string
    {
        return Equipesadmin::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $session = $this->requestStack->getSession();
        $exp = new UnicodeString('<sup>e</sup>');
        $repositoryEdition = $this->doctrine->getManager()->getRepository(Edition::class);
        $editioned = $session->get('edition')->getEd();
        if (new DateTime('now') < $session->get('edition')->getDateouverturesite()) {
            $editioned = $editioned - 1;
        }
        if (isset($_REQUEST['filters']['edition'])) {
            $editionId = $_REQUEST['filters']['edition'];

            $editioned = $repositoryEdition->findOneBy(['id' => $editionId]);
            $crud->setPageTitle('index', 'Liste des équipes de la ' . $editioned . $exp . ' édition');
        }
        if (isset($_REQUEST['lycees'])) {
            $crud->setPageTitle('index', 'Liste des établissements de la ' . $editioned . $exp . ' édition');
            $crud->setPageTitle(Crud::PAGE_EDIT, 'Détails du lycée');
        } else {
            $crud->setPageTitle('index', 'Liste des équipes de la ' . $editioned . $exp . ' édition');
            $crud->setPageTitle(Crud::PAGE_EDIT, 'Détails de l\'équipe');
        }


        $crud->setPageTitle(Crud::PAGE_NEW, 'Ajouter une équipe')
            ->setSearchFields(['id', 'lettre', 'numero', 'titreProjet', 'nomLycee', 'denominationLycee', 'lyceeLocalite', 'lyceeAcademie', 'prenomProf1', 'nomProf1', 'prenomProf2', 'nomProf2', 'uai', 'contribfinance', 'origineprojet', 'recompense', 'partenaire', 'description'])
            ->setPaginatorPageSize(50)
            ->renderContentMaximized();

        //->overrideTemplates(['layout'=> 'bundles/EasyAdminBundle/list_equipescia.html.twig', ]);

        return $crud;

    }

    public function configureActions(Actions $actions): Actions
    {   // dd($_REQUEST);
        $session = $this->requestStack->getSession();
        $editionId = 'na';
        $centreId = 'na';
        $selectionnee = 'na';


        $edition = $session->get('edition');

        $editionId = $this->requestStack->getSession()->get('edition')->getId();
        $date = new DateTime('now');
        if ($date < $this->requestStack->getSession()->get('edition')->getDateouverturesite()) {
            $edition = $this->requestStack->getSession()->get('edition');
            $editionId = $this->doctrine->getRepository(Edition::class)->findOneBy(['ed' => $edition->getEd() - 1])->getId();

        }


        if (isset($_REQUEST['filters']['centre'])) {
            $centreId = $_REQUEST['filters']['centre'];

        }
        if (isset($_REQUEST['filters']['edition'])) {
            $editionId = $_REQUEST['filters']['edition'];
            $editon = $this->doctrine->getRepository(Edition::class)->findOneBy(['id' => $editionId]);

        }
        if (isset($_REQUEST['filters']['selectionnee'])) {

            $selectionnee = $_REQUEST['filters']['selectionnee'];

        }

        if (!isset($_REQUEST['lycees'])) {

            $tableauexcel = Action::new('equipestableauexcel', 'Créer un tableau excel des équipes', 'fa fa_array',)
                // if the route needs parameters, you can define them:
                // 1) using an array
                ->linkToRoute('equipes_tableau_excel', ['ideditioncentre' => $editionId . '-' . $centreId . '-' . $selectionnee])
                ->createAsGlobalAction();

            //->displayAsButton()->setCssClass('btn btn-primary');

        }
        if (isset($_REQUEST['lycees'])) {


            $tableauexcel = Action::new('equipestableauexcel', 'Créer un tableau excel des lycées', 'fa fa_array',)
                // if the route needs parameters, you can define them:
                // 1) using an array
                ->linkToRoute('etablissements_tableau_excel', ['ideditioncentre' => $editionId . '-' . $centreId])
                ->createAsGlobalAction();
            //->displayAsButton()->setCssClass('btn btn-primary');
        }

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_INDEX, $tableauexcel)
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_SUPER_ADMIN');

    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(CustomEditionFilter::new('edition'))
            ->add(CustomCentreFilter::new('centre'))
            ->add('selectionnee');
    }

    public function configureFields(string $pageName): iterable
    {
        $session = $this->requestStack->getSession();
        if ($pageName == 'edit') {
            $idEquipe = $this->adminContextProvider->getContext()->getRequest()->query->get('entityId');
            $equipe = $this->doctrine->getRepository(Equipesadmin::class)->findOneBy(['id' => $idEquipe]);

            $uai = $equipe->getUai();
            $listProfs = $this->doctrine->getManager()->getRepository(User::class)->findBy(['uai' => $uai, 'isActive' => true]);
            $listeCentres = $this->doctrine->getManager()->getRepository(Centrescia::class)->findBy(['actif' => true], ['centre' => 'ASC']);
        } else {
            $listProfs = [];
            $listeCentres = [];
        }
        $repositoryUser = $this->doctrine->getManager()->getRepository(User::class);
        $numero = IntegerField::new('numero', 'N°');
        $lettre = ChoiceField::new('lettre')
            ->setChoices(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J', 'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O', 'P' => 'P', 'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T', 'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y', 'Z' => 'Z']);
        $titreProjet = TextField::new('titreProjet', 'Projet');
        $centre = AssociationField::new('centre')->setFormTypeOption('choices', $listeCentres)->setFormTypeOption('required', false);
        $IdProf1 = AssociationField::new('idProf1', 'Prof1')->setColumns(1)->setFormTypeOptions(['choices' => $listProfs])->setFormTypeOption('required', false);
        $IdProf2 = AssociationField::new('idProf2', 'Prof2')->setColumns(1)->setFormTypeOptions(['choices' => $listProfs])->setFormTypeOption('required', false);
        $selectionnee = BooleanField::new('selectionnee');
        $id = IntegerField::new('id', 'ID');
        $nomLycee = TextField::new('nomLycee', 'Lycée')->setColumns(10);
        $denominationLycee = TextField::new('denominationLycee');
        $lyceeLocalite = TextField::new('lyceeLocalite', 'Ville');
        $lyceeAcademie = TextField::new('lyceeAcademie', 'Académie');
        $uai = TextField::new('uaiId.uai', 'Code UAI')->setFormTypeOption('required', false);
        $lyceeAdresse = TextField::new('uaiId.adresse', 'Adresse');
        $lyceeCP = TextField::new('uaiId.codePostal', 'Code Postal');
        $lyceePays = TextField::new('uaiId.pays', 'Pays');
        $lyceeEmail = EmailField::new('uaiId.email', 'courriel');
        $contribfinance = ChoiceField::new('contribfinance', 'Contr. finance')->setChoices(['Prof1-avec versement anticipé de la contribution financière' => 'Prof1-avec versement anticipé de la contribution',
            'Prof1-avec remboursement à postériori des frais engagés' => 'Prof1-avec remboursement à postériori des frais engagés',
            'Prof2-avec versement anticipé de la contribution financière' => 'Prof2-avec versement anticipé de la contribution',
            'Prof2-avec remboursement à postériori des frais engagés' => 'Prof2-avec reboursement à postériori des frais engagés',
            'Gestionnaire du lycée' => 'Gestionnaire du lycée',
            'Autre' => 'Autre'
        ]);//TextField::new('contribfinance', 'Contr. finance')->setColumns(1);
        $origineprojet = TextField::new('origineprojet');
        //$recompense = TextField::new('recompense');
        $partenaire = TextField::new('partenaire');
        $createdAt = DateField::new('createdAt', 'Date d\'inscription');
        $description = TextareaField::new('description', 'Description du projet');
        $inscrite = BooleanField::new('inscrite');
        $retiree = BooleanField::new('retiree');
        $uaiId = AssociationField::new('uaiId')->setFormTypeOption('required', false);
        $edition = AssociationField::new('edition', 'Edition');
        $editionEd = TextareaField::new('edition.ed', 'Edition');
        $centreCentre = AssociationField::new('centre', 'Centre CIA');
        $lycee = TextareaField::new('Lycee');
        $prof1 = TextareaField::new('Prof1');
        $prof2 = TextareaField::new('Prof2');
        $nbeleves = IntegerField::new('nbeleves', 'Nbre elev')->setColumns(1);

        //dd($this->adminContextProvider->getContext());
        //dd($this->adminContextProvider->getContext()->getRequest()->attributes->get('_controller')[1]=='detail');


        if (Crud::PAGE_INDEX === $pageName) {
            if ($this->adminContextProvider->getContext()->getRequest()->query->get('lycees')) {

                return [$lyceePays, $lyceeAcademie, $nomLycee, $lyceeAdresse, $lyceeCP, $lyceeLocalite, $uai];
            } else {
                return [$numero, $lettre, $centreCentre, $titreProjet, $prof1, $prof2, $nomLycee, $lyceeLocalite, $lyceeAcademie, $selectionnee, $contribfinance, $nbeleves, $inscrite, $origineprojet, $createdAt];
            }
        } elseif (Crud::PAGE_DETAIL === $pageName) {

            if ($this->adminContextProvider->getContext()->getRequest()->query->get('menuIndex') == 7) {

                return [$editionEd, $lyceePays, $lyceeAcademie, $nomLycee, $lyceeAdresse, $lyceeLocalite, $lyceeCP, $lyceePays, $lyceeEmail, $uai, $retiree];
            } else {

                return [$id, $lettre, $numero, $centre, $titreProjet, $description, $selectionnee, $nomLycee, $denominationLycee, $lyceeLocalite, $lyceePays, $lyceeAcademie, $prof1, $prof2, $contribfinance, $origineprojet, $partenaire, $createdAt, $inscrite, $uai,];
            }
        } elseif (Crud::PAGE_NEW === $pageName) {
            return [$edition, $numero, $lettre, $uaiId, $lyceeAcademie, $titreProjet, $centre, $IdProf1, $IdProf2];
        } elseif (Crud::PAGE_EDIT === $pageName) {
            return [$edition, $numero, $lettre, $uaiId, $lyceeAcademie, $lyceeLocalite, $titreProjet, $centre, $selectionnee, $IdProf1, $IdProf2, $inscrite, $description, $contribfinance, $partenaire, $retiree];
        }

    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $session = $this->requestStack->getSession();
        $context = $this->adminContextProvider->getContext();
        $edition = $session->get('edition');
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        if (new Datetime('now') < $session->get('edition')->getDateouverturesite()) {
            $edition = $repositoryEdition->findOneBy(['ed' => $edition->getEd() - 1]);
        }
        $repositoryCentrescia = $this->doctrine->getManager()->getRepository(Centrescia::class);
        $qb = $this->doctrine->getRepository(Equipesadmin::class)->createQueryBuilder('e')
            ->andWhere('e.edition =:edition')
            ->andWhere('e.numero <:value')
            ->setParameter('edition', $edition)
            ->setParameter('value', 100);;
        if (isset($_REQUEST['filters'])) {
            if (isset($_REQUEST['filters']['edition'])) {
                $editionId = $_REQUEST['filters']['edition'];

                $editioned = $repositoryEdition->findOneBy(['id' => $editionId]);
                $qb->andWhere('e.edition =:edition')
                    ->setParameter('edition', $editioned);
            }

            if (isset($_REQUEST['filters']['centre'])) {
                $idCentre = $_REQUEST['filters']['centre'];
                $centre = $repositoryCentrescia->findOneBy(['id' => $idCentre]);
                $session->set('titrecentre', $centre);
                $qb->andWhere('e.centre =:centre')
                    ->setParameter('centre', $centre);
            }
            if (isset($_REQUEST['filters']['selectionnee'])) {
                $selectionnee = $_REQUEST['filters']['selectionnee'];
                $qb->andWhere('e.selectionnee =:selectionnee')
                    ->setParameter('selectionnee', $selectionnee);
            }

        }
        if ($this->adminContextProvider->getContext()->getRequest()->query->get('lycees')) {
            $qb->groupBy('e.uai');
        } else {
            $date = new \datetime('now');
            if ($date > $session->get('edition')->getDateclotureinscription()) {
                $qb->addOrderBy('e.numero', 'ASC');
            }
            if ($date <= $session->get('edition')->getDateclotureinscription()) {
                $qb->addOrderBy('e.numero', 'DESC');
            }
            if ($date > $session->get('edition')->getDatelimnat()) {
                $qb->addOrderBy('e.lettre', 'ASC');
            }
        }
        if (isset($_REQUEST['sort'])) {
            $qb->resetDQLPart('orderBy');
            $sort = $_REQUEST['sort'];
            if (key($sort) == 'lettre') {
                $qb->addOrderBy('e.lettre', $sort['lettre']);
            }
            if (key($sort) == 'numero') {
                $qb->addOrderBy('e.numero', $sort['numero']);
            }
            if (key($sort) == 'selectionnee') {
                $qb->addOrderBy('e.selectionnee', $sort['selectionnee'])
                    ->addOrderBy('e.lettre', 'ASC');
            }
            if (key($sort) == 'lyceeAcademie') {
                $qb->addOrderBy('e.lyceeAcademie', $sort['lyceeAcademie'])
                    ->addOrderBy('e.nomLycee', 'ASC');
            }
            if (key($sort) == 'createdAt') {
                $qb->addOrderBy('e.createdAt', $sort['createdAt']);
            }
            if (key($sort) == 'centre') {
                $qb->join('e.centre', 'c')
                    ->addOrderBy('c.centre', $sort['centre']);
            }
        }

        return $qb;
    }

    /*  public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
      {
          $edition= $this->requestStack->getSession()->get('edition');
          if (date('now')<$this->requestStack->getSession()->get('dateouverturesite')){
              $edition=$this->doctrine->getRepository(Edition::class)->findOneBy(['ed'=>$edition->getEd()-1]);

          }

          $response = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

          if ($filters===null) {
              $response->andWhere('entity.edition =:edition')
                  ->setParameter('edition', $edition);
          }

          return $response;
          //return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters); // TODO: Change the autogenerated stub
      }
    */

    #[Route("/Admin/EquipesadminCrud/equipes_tableau_excel,{ideditioncentre}", name: "equipes_tableau_excel")]
    public function equipestableauexcel($ideditioncentre)
    {

        $idedition = explode('-', $ideditioncentre)[0];
        $idcentre = explode('-', $ideditioncentre)[1];

        if (isset(explode('-', $ideditioncentre)[2])) {
            $selectionnee = explode('-', $ideditioncentre)[2];

        };

        $repositoryEleve = $this->doctrine->getRepository(Elevesinter::class);
        $repositoryCentre = $this->doctrine->getRepository(Centrescia::class);
        $repositoryProf = $this->doctrine->getRepository(User::class);
        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $repositoryEquipes = $this->doctrine->getRepository(Equipesadmin::class);
        $edition = $repositoryEdition->findOneBy(['id' => $idedition]);


        $queryBuilder = $repositoryEquipes->createQueryBuilder('e')
            //->andWhere('e.inscrite = TRUE')
            ->andWhere('e.edition =:edition')
            ->setParameter('edition', $edition)
            ->andWhere('e.numero < 100');

        if ($selectionnee == 1) {
            $queryBuilder->addOrderBy('e.lettre', 'ASC');
        } else {
            $queryBuilder->addOrderBy('e.numero', 'ASC');
        }
        if (($idcentre != 0) and ($idcentre != 'na')) {
            $centre = $repositoryCentre->findOneBy(['id' => $idcentre]);

            $queryBuilder
                ->andWhere('e.centre =:centre')
                ->setParameter('centre', $centre);

        }
        if (($selectionnee != 'na')) {

            $queryBuilder
                ->andWhere('e.selectionnee  = 1');

        }
        if ($edition != null) {
            $queryBuilder->andWhere('e.edition =:edition')
                ->setParameter('edition', $edition);

            $numEdition = $edition->getEd() . "e édition";
        } else {
            $queryBuilder->leftJoin('e.edition', 'ed')
                ->OrderBy('ed.ed', 'ASC');
            $numEdition = '';
        }
        $liste_equipes = $queryBuilder->getQuery()->getResult();

        //dd($edition);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("CN  " . $numEdition . " -Tableau destiné au comité")
            ->setSubject("Tableau destiné au comité")
            ->setDescription("Office 2007 XLSX liste des équipes")
            ->setKeywords("Office 2007 XLSX")
            ->setCategory("Test result file");

        $sheet = $spreadsheet->getActiveSheet();
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V'] as $letter) {
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        $ligne = 1;

        $sheet
            ->setCellValue('A' . $ligne, 'Idequipe')
            ->setCellValue('B' . $ligne, 'Edition')
            ->setCellValue('C' . $ligne, 'Année')
            ->setCellValue('D' . $ligne, 'Centre')
            ->setCellValue('E' . $ligne, 'nom équipe')
            ->setCellValue('F' . $ligne, 'Numéro')
            ->setCellValue('G' . $ligne, 'Lettre')
            ->setCellValue('H' . $ligne, 'inscrite')
            ->setCellValue('I' . $ligne, 'sélectionnée')
            ->setCellValue('J' . $ligne, 'Nom du lycée')
            ->setCellValue('K' . $ligne, 'Commune')
            ->setCellValue('L' . $ligne, 'Académie')
            ->setCellValue('M' . $ligne, 'uai')
            ->setCellValue('N' . $ligne, 'Description')
            ->setCellValue('O' . $ligne, 'Origine du projet')
            ->setCellValue('P' . $ligne, 'Contribution financière à ')
            ->setCellValue('Q' . $ligne, 'Prof 1')
            ->setCellValue('R' . $ligne, 'mail prof1')
            ->setCellValue('S' . $ligne, 'Prof2')
            ->setCellValue('T' . $ligne, 'mail prof2')
            ->setCellValue('U' . $ligne, 'Nombre d\'élèves')
            ->setCellValue('V' . $ligne, 'Date de création');

        $ligne += 1;
        $styleArray = ['strikethrough' => 'on'];
        foreach ($liste_equipes as $equipe) {
            $nbEleves = count($repositoryEleve->findBy(['equipe' => $equipe]));
            $idprof1 = $equipe->getIdProf1();
            $idprof2 = $equipe->getIdProf2();
            $prof1 = $repositoryProf->findOneBy(['id' => $idprof1]);
            $prof2 = $repositoryProf->findOneBy(['id' => $idprof2]);
            $uai = $equipe->getUaiId();

            $sheet->setCellValue('A' . $ligne, $equipe->getId())
                ->setCellValue('B' . $ligne, $equipe->getEdition()->getEd())
                ->setCellValue('C' . $ligne, $equipe->getEdition()->getAnnee());
            if ($equipe->getCentre() != null) {
                $sheet->setCellValue('D' . $ligne, $equipe->getCentre()->getCentre());
            }
            $sheet->setCellValue('E' . $ligne, $equipe->getTitreprojet())
                ->setCellValue('F' . $ligne, $equipe->getNumero())
                ->setCellValue('G' . $ligne, $equipe->getLettre())
                ->setCellValue('H' . $ligne, $equipe->getInscrite());
            if ($equipe->getInscrite() == 0) {
                $range = 'A' . strval($ligne) . ':W' . strval($ligne);
                $sheet->getStyle($range)->getFont()->setStrikethrough(true);

            }
            $sheet->setCellValue('I' . $ligne, $equipe->getSelectionnee())
                ->setCellValue('J' . $ligne, $uai->getNom())
                ->setCellValue('K' . $ligne, $uai->getCommune())
                ->setCellValue('L' . $ligne, $uai->getAcademie())
                ->setCellValue('M' . $ligne, $uai->getUai())
                ->setCellValue('N' . $ligne, $equipe->getDescription())
                ->setCellValue('O' . $ligne, $equipe->getOrigineprojet())
                ->setCellValue('P' . $ligne, $equipe->getContribfinance())
                ->setCellValue('Q' . $ligne, $prof1->getNomPrenom())
                ->setCellValue('R' . $ligne, $prof1->getEmail());
            if ($prof2 != null) {
                $sheet->setCellValue('S' . $ligne, $prof2->getNomPrenom())
                    ->setCellValue('T' . $ligne, $prof2->getEmail());
            }
            $sheet->setCellValue('U' . $ligne, $nbEleves)
                ->setCellValue('V' . $ligne, $equipe->getCreatedAt());
            $ligne += 1;
        }
        $sheet->getColumnDimension('E')->setAutoSize(false);
        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->getColumnDimension('N')->setAutoSize(false);
        $sheet->getColumnDimension('N')->setWidth(40);
        $sheet->getColumnDimension('O')->setAutoSize(false);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->getColumnDimension('P')->setAutoSize(false);
        $sheet->getColumnDimension('P')->setWidth(12.0, 'cm');
        $sheet->getColumnDimension('Q')->setAutoSize(false);
        $sheet->getColumnDimension('Q')->setWidth(8.0, 'cm');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="equipes.xls"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        //$writer= PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        //$writer =  \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        // $writer =IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_end_clean();
        $writer->save('php://output');

    }

    /**
     * @Route("/Admin/EquipesadminCrud/etablissements_tableau_excel,{ideditioncentre}", name="etablissements_tableau_excel")
     */
    public function etablissementstableauexcel($ideditioncentre)
    {
        $idedition = explode('-', $ideditioncentre)[0];
        $idcentre = explode('-', $ideditioncentre)[1];


        $repositoryCentre = $this->doctrine->getRepository(Centrescia::class);

        $repositoryEdition = $this->doctrine->getRepository(Edition::class);
        $repositoryEquipes = $this->doctrine->getRepository(Equipesadmin::class);
        $edition = $repositoryEdition->findOneBy(['id' => $idedition]);


        $queryBuilder = $repositoryEquipes->createQueryBuilder('e')
            ->andWhere('e.inscrite = TRUE')
            ->groupBy('e.nomLycee');
        if ($idedition != 0) {
            $queryBuilder->andWhere('e.edition =:edition')
                ->setParameter('edition', $edition);
        }
        $queryBuilder->andWhere('e.numero < 100');


        if (($idcentre != 0) and ($idcentre != 'na')) {
            $centre = $repositoryCentre->findOneBy(['id' => $idcentre]);
            $queryBuilder
                ->andWhere('e.centre =:centre')
                ->setParameter('centre', $centre)
                ->addOrderBy('e.edition', 'ASC');
        }


        $liste_lycees = $queryBuilder->getQuery()->getResult();
        if ($edition != null) {
            $numEdition = $edition->getEd() . "e édition";
        } else {
            $numEdition = '';
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("Olymphys")
            ->setLastModifiedBy("Olymphys")
            ->setTitle("CN  " . $numEdition . " -Tableau destiné au comité")
            ->setSubject("Tableau destiné au comité")
            ->setDescription("Office 2007 XLSX liste des établissements")
            ->setKeywords("Office 2007 XLSX")
            ->setCategory("Test result file");

        $sheet = $spreadsheet->getActiveSheet();
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T'] as $letter) {
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        $ligne = 1;

        $sheet
            ->setCellValue('A' . $ligne, 'Edition')
            ->setCellValue('B' . $ligne, 'lycée')
            ->setCellValue('C' . $ligne, 'nom')
            ->setCellValue('D' . $ligne, 'Adresse')
            ->setCellValue('E' . $ligne, 'Code Postal')
            ->setCellValue('F' . $ligne, 'Commune')
            ->setCellValue('G' . $ligne, 'Académie')
            ->setCellValue('H' . $ligne, 'UAI');

        $ligne += 1;

        foreach ($liste_lycees as $lycee) {
            $uai = $lycee->getUaiId();

            $sheet->setCellValue('A' . $ligne, $lycee->getEdition())
                ->setCellValue('B' . $ligne, $uai->getDenominationPrincipale())
                ->setCellValue('C' . $ligne, $uai->getNom())
                ->setCellValue('D' . $ligne, $uai->getAdresse())
                ->setCellValue('E' . $ligne, $uai->getCodePostal())
                ->setCellValue('F' . $ligne, $uai->getCommune())
                ->setCellValue('G' . $ligne, $uai->getAcademie())
                ->setCellValue('H' . $ligne, $uai->getUai());

            $ligne += 1;
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="lycées.xls"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        //$writer= PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        //$writer =  \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        // $writer =IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_end_clean();
        $writer->save('php://output');

    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {   //il faut supprimer les autorisations photos des élèves, les élèves, les fichiers de l'équipe et supprimer l'équipe de la table professeur avant de supprimer l'équipe
        $listeEleves = $this->doctrine->getRepository(Elevesinter::class)->createQueryBuilder('el')
            ->where('el.equipe =:equipe')
            ->setParameter('equipe', $entityInstance)
            ->getQuery()->getResult();
        $fichiers = $this->doctrine->getRepository(Fichiersequipes::class)->createQueryBuilder('f')
            ->where('f.equipe =:equipe')
            ->setParameter('equipe', $entityInstance)
            ->getQuery()->getResult();;
        $profs = $this->doctrine->getRepository(Professeurs::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.equipes', 'eq')
            ->where('eq =:equipe')
            ->setParameter('equipe', $entityInstance)
            ->getQuery()->getResult();;

        if ($listeEleves) {
            foreach ($listeEleves as $eleve) {
                if ($eleve->getAutorisationphotos()) {
                    $autorisation = $eleve->getAutorisationphotos();
                    $eleve->setAutorisationphotos(null);
                    $entityManager->remove($autorisation);
                    $entityManager->flush();
                }
                $entityManager->remove($eleve);
                $entityManager->flush();
            }
        }
        if ($fichiers) {
            foreach ($fichiers as $fichier) {
                $entityManager->remove($fichier);
                $entityManager->flush();
            }
        }
        if ($profs) {
            foreach ($profs as $prof) {
                $prof->removeEquipe($entityInstance);
                $entityManager->persist($prof);
                $entityManager->flush();
            }
        }
        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $uai = $entityInstance->getUaiId();
        if ($uai !== null) {
            $entityInstance->setUai($uai->getUai());
            $entityInstance->setNomLycee($uai->getNom());
            $entityInstance->setLyceeLocalite($uai->getCommune());
            $entityInstance->setLyceeAcademie($uai->getAcademie());
            $maj_profsequipes = new Maj_profsequipes($this->doctrine);
            $maj_profsequipes->maj_profsequipes($entityInstance);
        }
        $rempliOdpfEquipesPassees = new OdpfRempliEquipesPassees($this->doctrine);
        $rempliOdpfEquipesPassees->OdpfRempliEquipePassee($entityInstance);

        parent::updateEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }
}
