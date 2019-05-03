<?php

namespace App\Repository;

use App\Entity\Flickr;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Flickr|null find($id, $lockMode = null, $lockVersion = null)
 * @method Flickr|null findOneBy(array $criteria, array $orderBy = null)
 * @method Flickr[]    findAll()
 * @method Flickr[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FlickrRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Flickr::class);
    }

    // /**
    //  * @return Flickr[] Returns an array of Flickr objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Flickr
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
