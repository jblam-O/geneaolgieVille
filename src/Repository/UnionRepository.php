<?php

namespace App\Repository;

use App\Entity\Family;
use App\Entity\Union;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Union>
 */
class UnionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Union::class);
    }

    /** @return list<\App\Entity\Union> */
    public function findForFamily(Family $family): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.person1', 'partner')
            ->andWhere('partner.family = :family')
            ->setParameter('family', $family)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Union[] Returns an array of Union objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Union
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
