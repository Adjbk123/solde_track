<?php

namespace App\Repository;

use App\Entity\Mouvement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mouvement>
 */
class MouvementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mouvement::class);
    }

    public function save(Mouvement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Mouvement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Mouvement[] Returns an array of Mouvement objects
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Mouvement[] Returns an array of Mouvement objects by type
     */
    public function findByUserAndType($user, string $type): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('m.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Mouvement[] Returns an array of Mouvement objects by statut
     */
    public function findByUserAndStatut($user, string $statut): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', $statut)
            ->orderBy('m.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le solde total d'un utilisateur
     */
    public function getSoldeTotal($user): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(CASE WHEN m.type IN (:entree, :dette_a_recevoir) THEN m.montantEffectif ELSE -m.montantEffectif END)')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->setParameter('entree', Mouvement::TYPE_ENTREE)
            ->setParameter('dette_a_recevoir', Mouvement::TYPE_DETTE_A_RECEVOIR)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le solde d'un projet
     */
    public function getSoldeProjet($user, $projet): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(CASE WHEN m.type IN (:entree, :dette_a_recevoir) THEN m.montantEffectif ELSE -m.montantEffectif END)')
            ->andWhere('m.user = :user')
            ->andWhere('m.projet = :projet')
            ->setParameter('user', $user)
            ->setParameter('projet', $projet)
            ->setParameter('entree', Mouvement::TYPE_ENTREE)
            ->setParameter('dette_a_recevoir', Mouvement::TYPE_DETTE_A_RECEVOIR)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
