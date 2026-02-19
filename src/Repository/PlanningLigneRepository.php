<?php

namespace App\Repository;

use App\Entity\PlanningLigne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanningLigne>
 *
 * @method PlanningLigne|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlanningLigne|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlanningLigne[]    findAll()
 * @method PlanningLigne[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlanningLigneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningLigne::class);
    }

    public function save(PlanningLigne $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PlanningLigne $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}