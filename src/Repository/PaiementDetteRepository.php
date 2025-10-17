<?php

namespace App\Repository;

use App\Entity\PaiementDette;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementDette>
 */
class PaiementDetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementDette::class);
    }

    /**
     * Trouve tous les paiements d'un utilisateur
     */
    public function findByUser(User $user, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC');

        if (isset($filters['dette_id'])) {
            $qb->andWhere('p.dette = :dette')
               ->setParameter('dette', $filters['dette_id']);
        }

        if (isset($filters['statutPaiement'])) {
            $qb->andWhere('p.statutPaiement = :statutPaiement')
               ->setParameter('statutPaiement', $filters['statutPaiement']);
        }

        if (isset($filters['typePaiement'])) {
            $qb->andWhere('p.typePaiement = :typePaiement')
               ->setParameter('typePaiement', $filters['typePaiement']);
        }

        if (isset($filters['date_debut'])) {
            $qb->andWhere('p.datePaiement >= :dateDebut')
               ->setParameter('dateDebut', $filters['date_debut']);
        }

        if (isset($filters['date_fin'])) {
            $qb->andWhere('p.datePaiement <= :dateFin')
               ->setParameter('dateFin', $filters['date_fin']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les paiements d'une dette spécifique
     */
    public function findByDette(int $detteId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.dette = :dette')
            ->setParameter('dette', $detteId)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total des paiements pour une dette
     */
    public function getTotalPaiementsDette(int $detteId): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant) as total')
            ->where('p.dette = :dette')
            ->andWhere('p.statutPaiement = :statutConfirme')
            ->setParameter('dette', $detteId)
            ->setParameter('statutConfirme', PaiementDette::STATUT_CONFIRME)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le total des paiements par période
     */
    public function getTotalPaiementsPeriode(User $user, \DateTime $debut, \DateTime $fin): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant) as total')
            ->where('p.utilisateur = :user')
            ->andWhere('p.datePaiement BETWEEN :debut AND :fin')
            ->andWhere('p.statutPaiement = :statutConfirme')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutConfirme', PaiementDette::STATUT_CONFIRME)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve les paiements récents
     */
    public function findPaiementsRecents(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->andWhere('p.statutPaiement = :statutConfirme')
            ->setParameter('user', $user)
            ->setParameter('statutConfirme', PaiementDette::STATUT_CONFIRME)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements en attente
     */
    public function findPaiementsEnAttente(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->andWhere('p.statutPaiement = :statutEnAttente')
            ->setParameter('user', $user)
            ->setParameter('statutEnAttente', PaiementDette::STATUT_EN_ATTENTE)
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements par type
     */
    public function findByType(User $user, string $typePaiement): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->andWhere('p.typePaiement = :typePaiement')
            ->setParameter('user', $user)
            ->setParameter('typePaiement', $typePaiement)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les paiements avec pagination
     */
    public function findByUserWithPagination(User $user, int $page = 1, int $limit = 20, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if (isset($filters['dette_id'])) {
            $qb->andWhere('p.dette = :dette')
               ->setParameter('dette', $filters['dette_id']);
        }

        if (isset($filters['statutPaiement'])) {
            $qb->andWhere('p.statutPaiement = :statutPaiement')
               ->setParameter('statutPaiement', $filters['statutPaiement']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de paiements pour un utilisateur
     */
    public function countByUser(User $user, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.utilisateur = :user')
            ->setParameter('user', $user);

        if (isset($filters['dette_id'])) {
            $qb->andWhere('p.dette = :dette')
               ->setParameter('dette', $filters['dette_id']);
        }

        if (isset($filters['statutPaiement'])) {
            $qb->andWhere('p.statutPaiement = :statutPaiement')
               ->setParameter('statutPaiement', $filters['statutPaiement']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
