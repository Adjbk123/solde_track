<?php

namespace App\Repository;

use App\Entity\Dette;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dette>
 */
class DetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dette::class);
    }

    /**
     * Trouve toutes les dettes d'un utilisateur avec filtres optionnels
     */
    public function findByUser(User $user, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.date', 'DESC');

        if (isset($filters['typeDette'])) {
            $qb->andWhere('d.typeDette = :typeDette')
               ->setParameter('typeDette', $filters['typeDette']);
        }

        if (isset($filters['statutDette'])) {
            $qb->andWhere('d.statutDette = :statutDette')
               ->setParameter('statutDette', $filters['statutDette']);
        }

        if (isset($filters['contact_id'])) {
            $qb->andWhere('d.contact = :contact')
               ->setParameter('contact', $filters['contact_id']);
        }

        if (isset($filters['compte_id'])) {
            $qb->andWhere('d.compte = :compte')
               ->setParameter('compte', $filters['compte_id']);
        }

        if (isset($filters['date_debut'])) {
            $qb->andWhere('d.date >= :dateDebut')
               ->setParameter('dateDebut', $filters['date_debut']);
        }

        if (isset($filters['date_fin'])) {
            $qb->andWhere('d.date <= :dateFin')
               ->setParameter('dateFin', $filters['date_fin']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les dettes en retard
     */
    public function findDettesEnRetard(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.dateEcheance < :aujourdhui')
            ->andWhere('d.statutDette != :statutPayee')
            ->setParameter('user', $user)
            ->setParameter('aujourdhui', new \DateTime())
            ->setParameter('statutPayee', Dette::STATUT_PAYEE)
            ->orderBy('d.dateEcheance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dettes avec échéance proche
     */
    public function findDettesEcheanceProche(User $user, int $jours = 7): array
    {
        $dateLimite = (new \DateTime())->modify("+{$jours} days");

        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.dateEcheance BETWEEN :aujourdhui AND :dateLimite')
            ->andWhere('d.statutDette != :statutPayee')
            ->setParameter('user', $user)
            ->setParameter('aujourdhui', new \DateTime())
            ->setParameter('dateLimite', $dateLimite)
            ->setParameter('statutPayee', Dette::STATUT_PAYEE)
            ->orderBy('d.dateEcheance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des dettes par type
     */
    public function getTotalParType(User $user, string $typeDette): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.montantPrincipal) as total')
            ->where('d.user = :user')
            ->andWhere('d.typeDette = :typeDette')
            ->andWhere('d.statutDette != :statutAnnulee')
            ->setParameter('user', $user)
            ->setParameter('typeDette', $typeDette)
            ->setParameter('statutAnnulee', Dette::STATUT_ANNULEE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le total des dettes payées par type
     */
    public function getTotalPayeParType(User $user, string $typeDette): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('SUM(d.montantEffectif) as total')
            ->where('d.user = :user')
            ->andWhere('d.typeDette = :typeDette')
            ->andWhere('d.statutDette = :statutPayee')
            ->setParameter('user', $user)
            ->setParameter('typeDette', $typeDette)
            ->setParameter('statutPayee', Dette::STATUT_PAYEE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve les dettes par contact
     */
    public function findByContact(User $user, int $contactId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.contact = :contact')
            ->setParameter('user', $user)
            ->setParameter('contact', $contactId)
            ->orderBy('d.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dettes par compte
     */
    public function findByCompte(User $user, int $compteId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.compte = :compte')
            ->setParameter('user', $user)
            ->setParameter('compte', $compteId)
            ->orderBy('d.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dettes par période
     */
    public function findByPeriode(User $user, \DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.date BETWEEN :debut AND :fin')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('d.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dettes avec pagination
     */
    public function findByUserWithPagination(User $user, int $page = 1, int $limit = 20, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.date', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Appliquer les filtres
        if (isset($filters['typeDette'])) {
            $qb->andWhere('d.typeDette = :typeDette')
               ->setParameter('typeDette', $filters['typeDette']);
        }

        if (isset($filters['statutDette'])) {
            $qb->andWhere('d.statutDette = :statutDette')
               ->setParameter('statutDette', $filters['statutDette']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de dettes pour un utilisateur
     */
    public function countByUser(User $user, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.user = :user')
            ->setParameter('user', $user);

        if (isset($filters['typeDette'])) {
            $qb->andWhere('d.typeDette = :typeDette')
               ->setParameter('typeDette', $filters['typeDette']);
        }

        if (isset($filters['statutDette'])) {
            $qb->andWhere('d.statutDette = :statutDette')
               ->setParameter('statutDette', $filters['statutDette']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}