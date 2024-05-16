<?php

namespace App\Controller;

use AllowDynamicProperties;
use App\Entity\AideEnLigne;
use App\Entity\CategorieAide;
use App\Entity\SousCategorieAide;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Iban;

#[AllowDynamicProperties] class AideEnLigneController extends AbstractController
{
    public function __construct(EntityManagerInterface $em){

        $this->em = $em;

    }
    #[Route('/aide_en_ligne', name: 'app_aide_en_ligne')]
    #[IsGranted("ROLE_COMITE")]
    public function index(): Response
    {
        $categoriesAide=$this->em->getRepository(CategorieAide::class)->findAll();
        $sousCategoriesAide=$this->em->getRepository(SousCategorieAide::class)->findAll();
        $roles=$this->getUser()->getRoles();

        $aides=$this->em->getRepository(AideEnLigne::class)->findAll();




        return $this->render('aide_en_ligne/index.html.twig', ['categories'=>$categoriesAide,'souscategories'=>$sousCategoriesAide,'aides'=>$aides]);
    }
    #[Route('/aide_en_ligne/article,{idArticle}', name: 'article_aide')]
    #[IsGranted("ROLE_COMITE")]
    public function articleAide($idArticle): Response
    {
        $article=$this->em->getRepository(AideEnLigne::class)->find($idArticle);
        $categoriesAide=$this->em->getRepository(CategorieAide::class)->findAll();
        $sousCategoriesAide=$this->em->getRepository(SousCategorieAide::class)->findAll();
        $aides=$this->em->getRepository(AideEnLigne::class)->findAll();
        return $this->render('aide_en_ligne/article.html.twig', ['article'=>$article , 'categories'=>$categoriesAide,'souscategories'=>$sousCategoriesAide,'aides'=>$aides]);
    }




}
