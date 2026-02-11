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
        parent::__construct($registry, BonTravail::class);
    }

    /**
     * Récupère le dernier numéro de BT enregistré en base.
     * On trie par ID de façon décroissante pour avoir le plus récent.
     */
    public function findLastNumero(): ?string
    {
        try {
            $result = $this->createQueryBuilder('b')
                ->select('b.numero')
                ->orderBy('b.id', 'DESC') 
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            // Retourne la chaîne (ex: "BT-26-0023") ou null si la table est vide
            return $result ? $result['numero'] : null;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}