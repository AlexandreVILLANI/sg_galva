<?php

namespace App\Repository;

use App\Entity\DechargementUrgence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DechargementUrgence>
 *
 * @method DechargementUrgence|null find($id, $lockMode = null, $lockVersion = null)
 * @method DechargementUrgence|null findOneBy(array $criteria, array $orderBy = null)
 * @method DechargementUrgence[]    findAll()
 * @method DechargementUrgence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DechargementUrgenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DechargementUrgence::class);
    }
}
