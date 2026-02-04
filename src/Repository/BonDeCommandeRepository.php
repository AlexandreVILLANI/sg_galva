<?php

namespace App\Repository;

use App\Entity\BonDeCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BonDeCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonDeCommande::class);
    }

    /**
     * Récupère le REFI le plus élevé
     */
    public function getLastRefi(): ?string
    {
        $result = $this->createQueryBuilder('b')
            ->select('b.refi')
            ->orderBy('b.id', 'DESC') 
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['refi'] : null;
    }
}