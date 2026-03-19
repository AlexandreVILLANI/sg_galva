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
    public function findUniqueForfaits(): array
    {
        $results = $this->createQueryBuilder('b')
            ->select('DISTINCT b.forfait')
            ->where('b.forfait IS NOT NULL')
            ->orderBy('b.forfait', 'ASC')
            ->getQuery()
            ->getResult();

        // On "aplatit" le tableau car Doctrine renvoie un tableau de tableaux [['forfait' => '...'], ...]
        return array_column($results, 'forfait');
    }

    /**
     * Recherche une commande par son REFI et charge ses photos
     */
    public function findOneByRefiWithPhotos(string $refi): ?BonDeCommande
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.photos', 'p')
            ->addSelect('p') // Charge les photos dans la même requête
            ->where('b.refi = :refi')
            ->setParameter('refi', $refi)
            ->getQuery()
            ->getOneOrNullResult();
    }
}