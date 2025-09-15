<?php

namespace App\Repository;

use App\Entity\Compte;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Compte>
 */
class CompteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compte::class);
    }

    /**
     * Trouve tous les comptes actifs d'un utilisateur
     */
    public function findActifsByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.actif = :actif')
            ->setParameter('user', $user)
            ->setParameter('actif', true)
            ->orderBy('c.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le compte principal d'un utilisateur
     */
    public function findComptePrincipalByUser(User $user): ?Compte
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.type = :type')
            ->andWhere('c.actif = :actif')
            ->setParameter('user', $user)
            ->setParameter('type', Compte::TYPE_COMPTE_PRINCIPAL)
            ->setParameter('actif', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les comptes par type pour un utilisateur
     */
    public function findByUserAndType(User $user, string $type): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.type = :type')
            ->andWhere('c.actif = :actif')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('actif', true)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le solde total de tous les comptes d'un utilisateur
     */
    public function getSoldeTotalByUser(User $user): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.soldeActuel) as total')
            ->andWhere('c.user = :user')
            ->andWhere('c.actif = :actif')
            ->setParameter('user', $user)
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Trouve les comptes avec un solde négatif
     */
    public function findComptesAvecSoldeNegatif(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.actif = :actif')
            ->andWhere('c.soldeActuel < 0')
            ->setParameter('user', $user)
            ->setParameter('actif', true)
            ->orderBy('c.soldeActuel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les comptes par devise
     */
    public function findByUserAndDevise(User $user, string $deviseCode): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.devise', 'd')
            ->andWhere('c.user = :user')
            ->andWhere('d.code = :deviseCode')
            ->andWhere('c.actif = :actif')
            ->setParameter('user', $user)
            ->setParameter('deviseCode', $deviseCode)
            ->setParameter('actif', true)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des comptes par type
     */
    public function getStatistiquesParType(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.type, COUNT(c.id) as nombre, SUM(c.soldeActuel) as total')
            ->andWhere('c.user = :user')
            ->andWhere('c.actif = :actif')
            ->setParameter('user', $user)
            ->setParameter('actif', true)
            ->groupBy('c.type')
            ->orderBy('total', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        $statistiques = [];
        foreach ($results as $result) {
            $statistiques[$result['type']] = [
                'type' => $result['type'],
                'typeLabel' => Compte::getTypes()[$result['type']] ?? 'Inconnu',
                'nombre' => (int) $result['nombre'],
                'total' => (float) $result['total']
            ];
        }

        return $statistiques;
    }

    /**
     * Trouve les comptes créés dans une période
     */
    public function findByUserAndDateRange(User $user, \DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.dateCreation >= :debut')
            ->andWhere('c.dateCreation <= :fin')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
