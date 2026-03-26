<?php

namespace App\Repository;

use App\Entity\BonLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonLivraison>
 *
 * @method BonLivraison|null find($id, $lockMode = null, $lockVersion = null)
 * @method BonLivraison|null findOneBy(array $criteria, array $orderBy = null)
 * @method BonLivraison[]    findAll()
 * @method BonLivraison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonLivraison::class);
    }
}