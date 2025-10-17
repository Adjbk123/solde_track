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
     * Récupère les mouvements d'un compte dans une période donnée
     */
    public function findByCompteAndDateRange($compte, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.compte = :compte')
            ->setParameter('compte', $compte)
            ->orderBy('m.date', 'DESC');

        if ($dateDebut) {
            $qb->andWhere('m.date >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('m.date <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les mouvements d'un utilisateur dans une période donnée
     */
    public function findByUserAndDateRange($user, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.compte', 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.date', 'DESC');

        if ($dateDebut) {
            $qb->andWhere('m.date >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('m.date <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getResult();
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
            ->select('SUM(CASE WHEN m.type IN (:entree, :pret) THEN m.montantEffectif ELSE -m.montantEffectif END)')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->setParameter('entree', Mouvement::TYPE_ENTREE)
            ->setParameter('pret', Mouvement::TYPE_PRET)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le solde d'une dépense prévue
     */
    public function getSoldeDepensePrevue($user, $depensePrevue): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(CASE WHEN m.type IN (:entree, :pret) THEN m.montantEffectif ELSE -m.montantEffectif END)')
            ->andWhere('m.user = :user')
            ->andWhere('m.depensePrevue = :depensePrevue')
            ->setParameter('user', $user)
            ->setParameter('depensePrevue', $depensePrevue)
            ->setParameter('entree', Mouvement::TYPE_ENTREE)
            ->setParameter('pret', Mouvement::TYPE_PRET)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le total par type de mouvement pour une période donnée
     */
    public function getTotalByType($user, string $type, \DateTime $debut, \DateTime $fin): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.montantEffectif)')
            ->andWhere('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.date >= :debut')
            ->andWhere('m.date <= :fin')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Récupère les dépenses par catégorie pour une période donnée
     */
    public function getDepensesParCategorie($user, \DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('m')
            ->select('c.id, c.nom, SUM(m.montantEffectif) as montant')
            ->join('m.categorie', 'c')
            ->andWhere('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.date >= :debut')
            ->andWhere('m.date <= :fin')
            ->setParameter('user', $user)
            ->setParameter('type', Mouvement::TYPE_SORTIE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.id, c.nom')
            ->orderBy('montant', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les entrées par catégorie pour une période donnée
     */
    public function getEntreesParCategorie($user, \DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('m')
            ->select('c.id, c.nom, SUM(m.montantEffectif) as montant')
            ->join('m.categorie', 'c')
            ->andWhere('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.date >= :debut')
            ->andWhere('m.date <= :fin')
            ->setParameter('user', $user)
            ->setParameter('type', Mouvement::TYPE_ENTREE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.id, c.nom')
            ->orderBy('montant', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
