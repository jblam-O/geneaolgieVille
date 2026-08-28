<?php

namespace App\Repository;

use App\Entity\Town;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Town> */
class TownRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Town::class); }

    public function findOneByNormalizedName(string $name): ?Town
    {
        return $this->createQueryBuilder('town')
            ->andWhere('LOWER(town.name) = LOWER(:name)')
            ->setParameter('name', trim($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
