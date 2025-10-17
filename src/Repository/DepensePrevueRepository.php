<?php

namespace App\Repository;

use App\Entity\DepensePrevue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepensePrevue>
 *
 * @method DepensePrevue|null find($id, $lockMode = null, $lockVersion = null)
 * @method DepensePrevue|null findOneBy(array $criteria, array $orderBy = null)
 * @method DepensePrevue[]    findAll()
 * @method DepensePrevue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepensePrevueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepensePrevue::class);
    }

    /**
     * Trouve les dépenses prévues en retard
     */
    public function findEnRetard(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.dateFinPrevue < :now')
            ->andWhere('d.statut != :termine')
            ->setParameter('now', new \DateTime())
            ->setParameter('termine', DepensePrevue::STATUT_TERMINE)
            ->orderBy('d.dateFinPrevue', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dépenses prévues avec budget dépassé
     */
    public function findBudgetDepasse(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.montantDepense > d.budgetPrevu')
            ->andWhere('d.budgetPrevu IS NOT NULL')
            ->andWhere('d.typeBudget != :inconnu')
            ->setParameter('inconnu', DepensePrevue::TYPE_BUDGET_INCONNU)
            ->orderBy('d.montantDepense', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les dépenses prévues nécessitant une attention
     */
    public function findNecessitantAttention(): array
    {
        return $this->createQueryBuilder('d')
            ->where('(d.dateFinPrevue < :now AND d.statut != :termine) OR (d.montantDepense > d.budgetPrevu AND d.budgetPrevu IS NOT NULL AND d.typeBudget != :inconnu)')
            ->setParameter('now', new \DateTime())
            ->setParameter('termine', DepensePrevue::STATUT_TERMINE)
            ->setParameter('inconnu', DepensePrevue::TYPE_BUDGET_INCONNU)
            ->orderBy('d.dateFinPrevue', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les statistiques des dépenses prévues
     */
    public function getStatistiques($user): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $user);

        // Total des budgets prévus
        $budgetTotal = $qb->select('SUM(d.budgetPrevu)')
            ->andWhere('d.budgetPrevu IS NOT NULL')
            ->andWhere('d.typeBudget != :inconnu')
            ->setParameter('inconnu', DepensePrevue::TYPE_BUDGET_INCONNU)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Total des montants dépensés
        $montantTotal = $qb->select('SUM(d.montantDepense)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Nombre par statut
        $parStatut = [];
        foreach (DepensePrevue::STATUTS as $statut => $label) {
            $count = $this->count(['user' => $user, 'statut' => $statut]);
            $parStatut[$statut] = ['label' => $label, 'count' => $count];
        }

        return [
            'budgetTotalPrevu' => (float) $budgetTotal,
            'montantTotalDepense' => (float) $montantTotal,
            'economieRealisee' => max(0, (float) $budgetTotal - (float) $montantTotal),
            'depassementTotal' => max(0, (float) $montantTotal - (float) $budgetTotal),
            'parStatut' => $parStatut,
        ];
    }
}
