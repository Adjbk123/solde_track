<?php

namespace App\Repository;

use App\Entity\Transfert;
use App\Entity\User;
use App\Entity\Compte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transfert>
 */
class TransfertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transfert::class);
    }

    /**
     * Trouve tous les transferts d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les transferts d'un compte (source ou destination)
     */
    public function findByCompte(Compte $compte): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.compteSource = :compte OR t.compteDestination = :compte')
            ->setParameter('compte', $compte)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les transferts entre deux comptes
     */
    public function findByComptes(Compte $compte1, Compte $compte2): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('(t.compteSource = :compte1 AND t.compteDestination = :compte2) OR (t.compteSource = :compte2 AND t.compteDestination = :compte1)')
            ->setParameter('compte1', $compte1)
            ->setParameter('compte2', $compte2)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les transferts dans une période
     */
    public function findByUserAndDateRange(User $user, \DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.date >= :debut')
            ->andWhere('t.date <= :fin')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le montant total des transferts sortants d'un compte
     */
    public function getMontantTotalSortant(Compte $compte): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.montant) as total')
            ->andWhere('t.compteSource = :compte')
            ->setParameter('compte', $compte)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le montant total des transferts entrants d'un compte
     */
    public function getMontantTotalEntrant(Compte $compte): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.montant) as total')
            ->andWhere('t.compteDestination = :compte')
            ->setParameter('compte', $compte)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le solde net des transferts d'un compte
     */
    public function getSoldeNetTransferts(Compte $compte): float
    {
        $entrant = $this->getMontantTotalEntrant($compte);
        $sortant = $this->getMontantTotalSortant($compte);
        
        return $entrant - $sortant;
    }

    /**
     * Trouve les transferts récents d'un utilisateur
     */
    public function findRecentsByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des transferts par devise
     */
    public function getStatistiquesParDevise(User $user): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('d.code as devise_code, d.nom as devise_nom, COUNT(t.id) as nombre, SUM(t.montant) as total')
            ->join('t.devise', 'd')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->groupBy('d.id, d.code, d.nom')
            ->orderBy('total', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        $statistiques = [];
        foreach ($results as $result) {
            $statistiques[$result['devise_code']] = [
                'devise_code' => $result['devise_code'],
                'devise_nom' => $result['devise_nom'],
                'nombre' => (int) $result['nombre'],
                'total' => (float) $result['total']
            ];
        }

        return $statistiques;
    }

    /**
     * Trouve les transferts par montant (supérieur à un seuil)
     */
    public function findByMontantMinimum(User $user, float $montantMinimum): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.montant >= :montantMinimum')
            ->setParameter('user', $user)
            ->setParameter('montantMinimum', $montantMinimum)
            ->orderBy('t.montant', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les transferts d'un mois donné
     */
    public function findByMois(User $user, int $annee, int $mois): array
    {
        $debut = new \DateTime("$annee-$mois-01");
        $fin = clone $debut;
        $fin->modify('last day of this month')->setTime(23, 59, 59);

        return $this->findByUserAndDateRange($user, $debut, $fin);
    }

    /**
     * Calcule le montant total des transferts d'un utilisateur
     */
    public function getMontantTotalByUser(User $user): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.montant) as total')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
