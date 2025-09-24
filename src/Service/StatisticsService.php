<?php

namespace App\Service;

use App\Entity\Mouvement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Calcule les statistiques globales pour un utilisateur
     */
    public function calculateGlobalStatistics(User $user, \DateTime $debut, \DateTime $fin): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);

        // Entrées (argent reçu)
        $entrees = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_ENTREE, $debut, $fin);
        $dettesARecevoir = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_RECEVOIR, $debut, $fin);
        
        // Sorties (argent dépensé)
        $sorties = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_SORTIE, $debut, $fin);
        $dettesAPayer = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_PAYER, $debut, $fin);

        $totalEntrees = $entrees + $dettesARecevoir;
        $totalSorties = $sorties + $dettesAPayer;
        $soldeNet = $totalEntrees - $totalSorties;

        return [
            'entrees' => [
                'total' => $totalEntrees,
                'detail' => [
                    'entrees' => $entrees,
                    'dettes_a_recevoir' => $dettesARecevoir
                ]
            ],
            'sorties' => [
                'total' => $totalSorties,
                'detail' => [
                    'sorties' => $sorties,
                    'dettes_a_payer' => $dettesAPayer
                ]
            ],
            'solde_net' => $soldeNet,
            'periode' => [
                'debut' => $debut->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d')
            ]
        ];
    }

    /**
     * Calcule les statistiques par type de mouvement
     */
    public function calculateStatisticsByMovementType(User $user, \DateTime $debut, \DateTime $fin): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        $types = [
            Mouvement::TYPE_ENTREE => 'Entrées',
            Mouvement::TYPE_SORTIE => 'Dépenses',
            Mouvement::TYPE_DETTE_A_PAYER => 'Dettes à payer',
            Mouvement::TYPE_DETTE_A_RECEVOIR => 'Dettes à recevoir'
        ];

        $statistics = [];
        foreach ($types as $type => $label) {
            $total = $mouvementRepo->getTotalByType($user, $type, $debut, $fin);
            $statistics[$type] = [
                'label' => $label,
                'total' => $total,
                'formatted' => number_format($total, 2, '.', '')
            ];
        }

        return $statistics;
    }

    /**
     * Calcule les statistiques par catégorie
     */
    public function calculateStatisticsByCategory(User $user, \DateTime $debut, \DateTime $fin): array
    {
        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->select('c.nom as categorie_nom, c.type as categorie_type, SUM(m.montantTotal) as total')
            ->join('m.categorie', 'c')
            ->where('m.user = :user')
            ->andWhere('m.date >= :debut')
            ->andWhere('m.date <= :fin')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->groupBy('c.id, c.nom, c.type')
            ->orderBy('total', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        $statistics = [];
        foreach ($results as $result) {
            $statistics[] = [
                'categorie' => $result['categorie_nom'],
                'type' => $result['categorie_type'],
                'total' => (float) $result['total'],
                'formatted' => number_format($result['total'], 2, '.', '')
            ];
        }

        return $statistics;
    }

    /**
     * Calcule les tendances sur plusieurs périodes
     */
    public function calculateTrends(User $user, string $periode, int $nbPeriodes): array
    {
        $tendances = [];
        
        for ($i = $nbPeriodes - 1; $i >= 0; $i--) {
            $periodeData = $this->getPeriodeOffset($periode, $i);
            $stats = $this->calculateGlobalStatistics($user, $periodeData['debut'], $periodeData['fin']);
            
            $tendances[] = [
                'periode' => $periodeData['debut']->format('Y-m-d') . ' / ' . $periodeData['fin']->format('Y-m-d'),
                'entrees' => number_format($stats['entrees']['total'], 2, '.', ''),
                'sorties' => number_format($stats['sorties']['total'], 2, '.', ''),
                'solde' => number_format($stats['solde_net'], 2, '.', '')
            ];
        }

        return $tendances;
    }

    /**
     * Calcule le solde des dettes
     */
    public function calculateDebtBalance(User $user, ?\DateTime $debut = null, ?\DateTime $fin = null): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        // Si aucune période n'est spécifiée, prendre toutes les dettes
        if ($debut === null) {
            $debut = new \DateTime('1900-01-01');
        }
        if ($fin === null) {
            $fin = new \DateTime();
        }
        
        $dettesAPayer = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_PAYER, $debut, $fin);
        $dettesARecevoir = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_RECEVOIR, $debut, $fin);
        
        return [
            'dettes_a_payer' => $dettesAPayer,
            'dettes_a_recevoir' => $dettesARecevoir,
            'solde_dettes' => $dettesARecevoir - $dettesAPayer,
            'net_positive' => $dettesARecevoir > $dettesAPayer
        ];
    }

    /**
     * Calcule les variations par rapport à la période précédente
     */
    public function calculateVariations(User $user, string $periode): array
    {
        $actuelle = $this->getPeriodeActuelle($periode);
        $precedente = $this->getPeriodePrecedente($periode);
        
        $statsActuelles = $this->calculateGlobalStatistics($user, $actuelle['debut'], $actuelle['fin']);
        $statsPrecedentes = $this->calculateGlobalStatistics($user, $precedente['debut'], $precedente['fin']);
        
        return [
            'entrees' => $this->calculerVariation($statsActuelles['entrees']['total'], $statsPrecedentes['entrees']['total']),
            'sorties' => $this->calculerVariation($statsActuelles['sorties']['total'], $statsPrecedentes['sorties']['total']),
            'solde' => $this->calculerVariation($statsActuelles['solde_net'], $statsPrecedentes['solde_net'])
        ];
    }

    /**
     * Obtient les entrées pour une période donnée
     */
    public function getEntreesByPeriod(User $user, \DateTime $debut, \DateTime $fin): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        $entrees = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_ENTREE, $debut, $fin);
        $dettesARecevoir = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_RECEVOIR, $debut, $fin);
        
        $totalEntrees = $entrees + $dettesARecevoir;
        
        return [
            'periode' => [
                'debut' => $debut->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d')
            ],
            'total' => $totalEntrees,
            'detail' => [
                'entrees_normales' => $entrees,
                'dettes_a_recevoir' => $dettesARecevoir
            ],
            'formatted' => [
                'total' => number_format($totalEntrees, 2, '.', ''),
                'entrees_normales' => number_format($entrees, 2, '.', ''),
                'dettes_a_recevoir' => number_format($dettesARecevoir, 2, '.', '')
            ]
        ];
    }

    /**
     * Obtient les sorties pour une période donnée
     */
    public function getSortiesByPeriod(User $user, \DateTime $debut, \DateTime $fin): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        $sorties = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_SORTIE, $debut, $fin);
        $dettesAPayer = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_PAYER, $debut, $fin);
        
        $totalSorties = $sorties + $dettesAPayer;
        
        return [
            'periode' => [
                'debut' => $debut->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d')
            ],
            'total' => $totalSorties,
            'detail' => [
                'sorties' => $sorties,
                'dettes_a_payer' => $dettesAPayer
            ],
            'formatted' => [
                'total' => number_format($totalSorties, 2, '.', ''),
                'sorties' => number_format($sorties, 2, '.', ''),
                'dettes_a_payer' => number_format($dettesAPayer, 2, '.', '')
            ]
        ];
    }

    /**
     * Obtient les entrées et sorties pour une période donnée
     */
    public function getEntreesSortiesByPeriod(User $user, \DateTime $debut, \DateTime $fin): array
    {
        $entrees = $this->getEntreesByPeriod($user, $debut, $fin);
        $sorties = $this->getSortiesByPeriod($user, $debut, $fin);
        
        $soldeNet = $entrees['total'] - $sorties['total'];
        
        return [
            'periode' => [
                'debut' => $debut->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d')
            ],
            'entrees' => $entrees,
            'sorties' => $sorties,
            'solde_net' => $soldeNet,
            'solde_net_formatted' => number_format($soldeNet, 2, '.', '')
        ];
    }

    /**
     * Calcule une variation en pourcentage
     */
    private function calculerVariation(float $actuel, float $precedent): array
    {
        if ($precedent == 0) {
            return [
                'valeur' => 0,
                'pourcentage' => 0,
                'tendance' => 'stable'
            ];
        }

        $variation = $actuel - $precedent;
        $pourcentage = ($variation / $precedent) * 100;
        
        $tendance = 'stable';
        if ($pourcentage > 5) $tendance = 'hausse';
        elseif ($pourcentage < -5) $tendance = 'baisse';

        return [
            'valeur' => $variation,
            'pourcentage' => round($pourcentage, 2),
            'tendance' => $tendance
        ];
    }

    /**
     * Obtient une période avec offset
     */
    private function getPeriodeOffset(string $periode, int $offset): array
    {
        $fin = new \DateTime();
        $fin->modify("+{$offset} {$periode}");
        
        $debut = clone $fin;
        switch ($periode) {
            case 'semaine':
                $debut->modify('-1 week');
                break;
            case 'mois':
                $debut->modify('-1 month');
                break;
            case 'trimestre':
                $debut->modify('-3 months');
                break;
            case 'annee':
                $debut->modify('-1 year');
                break;
            default:
                $debut->modify('-1 month');
        }
        
        return ['debut' => $debut, 'fin' => $fin];
    }

    /**
     * Obtient la période actuelle
     */
    private function getPeriodeActuelle(string $periode): array
    {
        $fin = new \DateTime();
        $debut = new \DateTime();
        
        switch ($periode) {
            case 'semaine':
                $debut->modify('monday this week');
                $fin->modify('sunday this week');
                break;
            case 'mois':
                $debut->modify('first day of this month');
                $fin->modify('last day of this month');
                break;
            case 'trimestre':
                $debut->modify('first day of this quarter');
                $fin->modify('last day of this quarter');
                break;
            case 'annee':
                $debut->modify('first day of this year');
                $fin->modify('last day of this year');
                break;
            default:
                $debut->modify('monday this week');
                $fin->modify('sunday this week');
        }
        
        return ['debut' => $debut, 'fin' => $fin];
    }

    /**
     * Obtient la période précédente
     */
    private function getPeriodePrecedente(string $periode): array
    {
        $fin = new \DateTime();
        $debut = new \DateTime();
        
        switch ($periode) {
            case 'semaine':
                $debut->modify('monday last week');
                $fin->modify('sunday last week');
                break;
            case 'mois':
                $debut->modify('first day of last month');
                $fin->modify('last day of last month');
                break;
            case 'trimestre':
                $debut->modify('first day of last quarter');
                $fin->modify('last day of last quarter');
                break;
            case 'annee':
                $debut->modify('first day of last year');
                $fin->modify('last day of last year');
                break;
            default:
                $debut->modify('monday last week');
                $fin->modify('sunday last week');
        }
        
        return ['debut' => $debut, 'fin' => $fin];
    }
}
