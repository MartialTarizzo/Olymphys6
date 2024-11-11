<?php

namespace App\Repository\Odpf;

use App\Entity\Fichiersequipes;
use App\Entity\Odpf\OdpfFichierspasses;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OdpfFichierspasses|null find($id, $lockMode = null, $lockVersion = null)
 * @method OdpfFichierspasses|null findOneBy(array $criteria, array $orderBy = null)
 * @method OdpfFichierspasses[]    findAll()
 * @method OdpfFichierspasses[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OdpfFichierspassesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OdpfFichierspasses::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(OdpfFichierspasses $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(OdpfFichierspasses $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
    /**
     * Used by Elastica to transform results to model
     *
     *
     */
    public function createSearchQueryBuilder($entityAlias)
    {
        $qb = $this->createQueryBuilder($entityAlias);

        $qb->select($entityAlias, 'g')
            ->innerJoin($entityAlias.'.groups', 'g');
            dd($qb);

        return $qb;
    }
}
