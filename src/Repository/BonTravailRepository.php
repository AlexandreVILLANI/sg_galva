<?php

namespace App\Repository;

use App\Entity\BonTravail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BonTravail>
 */
class BonTravailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // On utilise bien les deux points (::) ici
        parent::__construct($registry, BonTravail::class);
    }

    /**
     * Récupère le dernier numéro de BT enregistré
     */
    public function findLastNumero(): ?string
    {
        $result = $this->createQueryBuilder('b')
            ->select('b.numero')
            ->orderBy('b.id', 'DESC') 
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Si on a un résultat, on retourne la valeur de la colonne 'numero', sinon null
        return $result ? $result['numero'] : null;
    }
}