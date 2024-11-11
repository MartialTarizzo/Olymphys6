<?php

namespace App\Controller;

use App\Entity\Odpf\OdpfFichierspasses;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;

use FOS\ElasticaBundle\Finder\TransformedFinder;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;
use Mpdf\Tag\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Elastica\Util;
class SearchController extends AbstractController
{
    private PaginatedFinderInterface $finder;
    private RepositoryManagerInterface $manager;

    public function __construct(PaginatedFinderInterface $finder, RepositoryManagerInterface $manager)
    {
        $this->finder = $finder;
        $this->manager = $manager;
    }
    #[Route("search", name: "search")]
    public function searchAction(Request $request) : Response
    {


        // Option 1. Returns all users who have example.net in any of their mapped fields
        //$results = $this->finder->find('12',10);

        // Option 2. Returns a set of hybrid results that contain all Elasticsearch results
        // and their transformed counterparts. Each result is an instance of a HybridResult


        $paginator = $this->finder->findPaginated('Soleil');
        dd($paginator->getNbResults());
        // Option 3a. Pagerfanta'd resultset
        /** var Pagerfanta\Pagerfanta */
        $userPaginator = $this->finder->findPaginated('bob');
        $countOfResults = $userPaginator->getNbResults();

        // Option 3b. KnpPaginator resultset
        $paginator = $this->get('knp_paginator');
        $results = $this->finder->createPaginatorAdapter('bob');
        $pagination = $paginator->paginate($results, $page, 10);

        // You can specify additional options as the fourth parameter of Knp Paginator
        // paginate method to nested_filter and nested_sort

        $options = [
            'sortNestedPath' => 'owner',
            'sortNestedFilter' => new Query\Term(['enabled' => ['value' => true]]),
        ];

        // sortNestedPath and sortNestedFilter also accepts a callable
        // which takes the current sort field to get the correct sort path/filter

        $pagination = $paginator->paginate($results, $page, 10, $options);
    }
}