<?php

namespace App\Repository;

use App\Entity\DocumentBonLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentBonLivraison>
 *
 * @method DocumentBonLivraison|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentBonLivraison|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentBonLivraison[]    findAll()
 * @method DocumentBonLivraison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentBonLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentBonLivraison::class);
    }

    public function save(DocumentBonLivraison $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentBonLivraison $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
