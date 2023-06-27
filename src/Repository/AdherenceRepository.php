<?php

namespace App\Repository;

use App\Entity\Adherence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<adherence>
 *
 * @method Adherence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Adherence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Adherence[]    findAll()
 * @method Adherence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdherenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, adherence::class);
    }

    public function save(Adherence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Adherence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getUserAdherence(int $conceptId, int $adherentId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.concept = ' . $conceptId)
            ->andWhere('a.adherent = ' . $adherentId)
            ->getQuery()
            ->getResult();
    }

    public function getNumberOfAdherence(int $conceptId): int
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.adherent)')
            ->where('a.concept = ' . $conceptId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Adherence[] Returns an array of Adherence objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Adherence
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}