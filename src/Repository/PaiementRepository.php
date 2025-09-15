<?php

namespace App\Repository;

use App\Entity\Paiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Paiement>
 */
class PaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Paiement::class);
    }

    public function save(Paiement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Paiement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Paiement[] Returns an array of Paiement objects for a mouvement
     */
    public function findByMouvement($mouvement): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.mouvement = :mouvement')
            ->setParameter('mouvement', $mouvement)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des paiements pour un mouvement
     */
    public function getTotalPaiements($mouvement): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->andWhere('p.mouvement = :mouvement')
            ->setParameter('mouvement', $mouvement)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
