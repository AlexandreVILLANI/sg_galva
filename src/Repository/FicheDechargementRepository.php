<?php

namespace App\Repository;

use App\Entity\FicheDechargement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FicheDechargement>
 */
class FicheDechargementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FicheDechargement::class);
    }

//    /**
//     * @return FicheDechargement[] Returns an array of FicheDechargement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FicheDechargement
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    // src/Repository/FicheDechargementRepository.php

    public function findWithFilters(?string $client, ?string $cariste, ?string $date): array
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.client', 'c')
            ->leftJoin('f.cariste', 'u')
            ->addSelect('c', 'u')
            ->orderBy('f.date', 'DESC');

        if ($client) {
            $qb->andWhere('LOWER(c.nom) LIKE LOWER(:client)')
            ->setParameter('client', '%' . $client . '%');
        }

        if ($cariste) {
            $qb->andWhere('LOWER(u.prenom) LIKE LOWER(:cariste)')
            ->setParameter('cariste', '%' . $cariste . '%');
        }

        if ($date) {
            $startDate = new \DateTime($date . ' 00:00:00');
            $endDate = new \DateTime($date . ' 23:59:59');

            $qb->andWhere('f.date BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);
        }

        return $qb->getQuery()->getResult();
    }
}
