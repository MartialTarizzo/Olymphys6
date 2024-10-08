<?php


namespace App\Form\Type\Admin;

use App\Entity\Edition;
use App\Entity\Equipesadmin;

use App\Entity\Odpf\OdpfEditionsPassees;
use App\Entity\Odpf\OdpfEquipesPassees;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\DateTime;

//use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class CustomPhotosEquipesFilterType extends AbstractType
{

    private EntityManagerInterface $doctrine;
    private $requestStack;
    public function __construct(RequestStack $requestStack, EntityManagerInterface $doctrine)
    {
        $this->requestStack=$requestStack;
        $this->doctrine = $doctrine;
    }

    public function configureOptions(OptionsResolver $resolver) : void

    {
        $edition = $this->requestStack->getSession()->get('edition');

        if(new \DateTime('now')<$this->requestStack->getSession()->get('edition')->getDateouverturesite())
        {
            $edition=$this->doctrine->getRepository(Edition::class)->findOneBy(['ed'=>$edition->getEd()-1]);
        }

        $listeEquipe = $this->doctrine->getRepository(Equipesadmin::class)->createQueryBuilder('e')
            ->andWhere('e.edition =:edition')
            ->setParameter('edition', $edition)
            ->addOrderBy('e.numero', 'ASC')
            ->getQuery()->getResult();
        // $edition = $_SESSION['_sf2_attributes']['edition'];
        $resolver->setDefaults([
            'comparison_type_options' => ['type' => 'entity'],
            'value_type' => EntityType::class,
            'class' => Equipesadmin::class,
            'choices' => $listeEquipe
        ]);

    }

    public function getParent(): string
    {
        return EntityType::class;
    }


}
