<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Mouvement;
use App\Entity\Dette;
use App\Service\UserDeviseService;
use App\Service\StatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/statistiques', name: 'api_statistiques_')]
class StatistiquesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserDeviseService $userDeviseService,
        private StatisticsService $statisticsService
    ) {}

    #[Route('/resume', name: 'resume', methods: ['GET'])]
    public function getResume(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'semaine');
        $debut = $this->getDateDebut($periode);
        $fin = new \DateTime();

        // Utiliser le nouveau service de statistiques
        $statistics = $this->statisticsService->calculateGlobalStatistics($user, $debut, $fin);
        $variations = $this->statisticsService->calculateVariations($user, $periode);

        return new JsonResponse([
            'periode' => $periode,
            'entrees' => [
                'montant' => number_format($statistics['entrees']['total'], 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $statistics['entrees']['total']),
                'variation' => $variations['entrees']['pourcentage'],
                'variationFormatted' => ($variations['entrees']['pourcentage'] >= 0 ? '+' : '') . number_format($variations['entrees']['pourcentage'], 1) . '%'
            ],
            'sorties' => [
                'montant' => number_format($statistics['sorties']['total'], 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $statistics['sorties']['total']),
                'variation' => $variations['sorties']['pourcentage'],
                'variationFormatted' => ($variations['sorties']['pourcentage'] >= 0 ? '+' : '') . number_format($variations['sorties']['pourcentage'], 1) . '%'
            ],
            'soldeTotal' => [
                'montant' => number_format($statistics['solde_net'], 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $statistics['solde_net'])
            ],
            'detail' => [
                'entrees' => [
                    'entrees_normales' => number_format($statistics['entrees']['detail']['entrees'] ?? 0, 2, '.', ''),
                    'dettes_a_recevoir' => number_format($statistics['entrees']['detail']['dettes_a_recevoir'] ?? 0, 2, '.', '')
                ],
                'sorties' => [
                    'depenses' => number_format($statistics['sorties']['detail']['depenses'] ?? 0, 2, '.', ''),
                    'dettes_a_payer' => number_format($statistics['sorties']['detail']['dettes_a_payer'] ?? 0, 2, '.', '')
                ]
            ]
        ]);
    }

    #[Route('/evolution-depenses', name: 'evolution_depenses', methods: ['GET'])]
    #[Route('/evolution-sorties', name: 'evolution_sorties', methods: ['GET'])]
    public function getEvolutionDepenses(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'semaine');
        $type = $request->query->get('type', 'depenses'); // depenses/sorties, entrees, solde
        
        // Support de l'ancien terme "depenses" et du nouveau "sorties"
        if ($type === 'sorties') {
            $type = 'depenses';
        }

        $donnees = $this->getDonneesEvolution($user, $periode, $type);

        return new JsonResponse([
            'periode' => $periode,
            'type' => $type,
            'donnees' => $donnees,
            'message' => 'Graphique en cours de développement'
        ]);
    }

    #[Route('/depenses-par-categorie', name: 'depenses_par_categorie', methods: ['GET'])]
    #[Route('/sorties-par-categorie', name: 'sorties_par_categorie', methods: ['GET'])]
    public function getDepensesParCategorie(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'mois');
        $debut = $this->getDateDebut($periode);
        $fin = new \DateTime();

        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        $depensesParCategorie = $mouvementRepo->getDepensesParCategorie($user, $debut, $fin);

        $totalDepenses = array_sum(array_column($depensesParCategorie, 'montant'));

        $categories = [];
        foreach ($depensesParCategorie as $categorie) {
            $pourcentage = $totalDepenses > 0 ? ($categorie['montant'] / $totalDepenses) * 100 : 0;
            $categories[] = [
                'id' => $categorie['id'],
                'nom' => $categorie['nom'],
                'montant' => number_format($categorie['montant'], 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $categorie['montant']),
                'pourcentage' => round($pourcentage, 1)
            ];
        }

        return new JsonResponse([
            'periode' => $periode,
            'totalDepenses' => number_format($totalDepenses, 2, '.', ''),
            'totalDepensesFormatted' => $this->userDeviseService->formatAmount($user, $totalDepenses),
            'categories' => $categories
        ]);
    }

    #[Route('/entrees-par-categorie', name: 'entrees_par_categorie', methods: ['GET'])]
    public function getEntreesParCategorie(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'mois');
        $debut = $this->getDateDebut($periode);
        $fin = new \DateTime();

        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        $entreesParCategorie = $mouvementRepo->getEntreesParCategorie($user, $debut, $fin);

        $totalEntrees = array_sum(array_column($entreesParCategorie, 'montant'));

        $categories = [];
        foreach ($entreesParCategorie as $categorie) {
            $pourcentage = $totalEntrees > 0 ? ($categorie['montant'] / $totalEntrees) * 100 : 0;
            $categories[] = [
                'id' => $categorie['id'],
                'nom' => $categorie['nom'],
                'montant' => number_format($categorie['montant'], 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $categorie['montant']),
                'pourcentage' => round($pourcentage, 1)
            ];
        }

        return new JsonResponse([
            'periode' => $periode,
            'totalEntrees' => number_format($totalEntrees, 2, '.', ''),
            'totalEntreesFormatted' => $this->userDeviseService->formatAmount($user, $totalEntrees),
            'categories' => $categories
        ]);
    }

    #[Route('/comparaison-periodes', name: 'comparaison_periodes', methods: ['GET'])]
    public function getComparaisonPeriodes(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'mois');
        
        // Utiliser le nouveau service de statistiques
        $actuelle = $this->getPeriodeActuelle($periode);
        $actuelleStats = $this->statisticsService->calculateGlobalStatistics($user, $actuelle['debut'], $actuelle['fin']);
        
        $precedente = $this->getPeriodePrecedente($periode);
        $precedenteStats = $this->statisticsService->calculateGlobalStatistics($user, $precedente['debut'], $precedente['fin']);

        return new JsonResponse([
            'periode' => $periode,
            'actuelle' => [
                'debut' => $actuelle['debut']->format('Y-m-d'),
                'fin' => $actuelle['fin']->format('Y-m-d'),
                'entrees' => [
                    'montant' => number_format($actuelleStats['entrees']['total'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $actuelleStats['entrees']['total'])
                ],
                'sorties' => [
                    'montant' => number_format($actuelleStats['sorties']['total'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $actuelleStats['sorties']['total'])
                ],
                'solde' => [
                    'montant' => number_format($actuelleStats['solde_net'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $actuelleStats['solde_net'])
                ]
            ],
            'precedente' => [
                'debut' => $precedente['debut']->format('Y-m-d'),
                'fin' => $precedente['fin']->format('Y-m-d'),
                'entrees' => [
                    'montant' => number_format($precedenteStats['entrees']['total'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $precedenteStats['entrees']['total'])
                ],
                'sorties' => [
                    'montant' => number_format($precedenteStats['sorties']['total'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $precedenteStats['sorties']['total'])
                ],
                'solde' => [
                    'montant' => number_format($precedenteStats['solde_net'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $precedenteStats['solde_net'])
                ]
            ],
            'variations' => [
                'entrees' => $this->calculerVariation($actuelleStats['entrees']['total'], $precedenteStats['entrees']['total']),
                'sorties' => $this->calculerVariation($actuelleStats['sorties']['total'], $precedenteStats['sorties']['total']),
                'solde' => $this->calculerVariation($actuelleStats['solde_net'], $precedenteStats['solde_net'])
            ]
        ]);
    }

    #[Route('/tendances', name: 'tendances', methods: ['GET'])]
    public function getTendances(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'mois');
        $nbPeriodes = (int) $request->query->get('nb_periodes', 6);

        // Utiliser le nouveau service de statistiques
        $tendances = $this->statisticsService->calculateTrends($user, $periode, $nbPeriodes);

        return new JsonResponse([
            'periode' => $periode,
            'nbPeriodes' => $nbPeriodes,
            'tendances' => $tendances
        ]);
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $categorieRepo = $this->entityManager->getRepository(\App\Entity\Categorie::class);
        $categories = $categorieRepo->findBy(['user' => $user], ['nom' => 'ASC']);

        $categoriesData = [];
        foreach ($categories as $categorie) {
            $categoriesData[] = [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNom(),
                'type' => $categorie->getType(),
                'dateCreation' => $categorie->getDateCreation()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'categories' => $categoriesData
        ]);
    }

    /**
     * Obtenir les entrées pour une période donnée
     */
    #[Route('/entrees', name: 'entrees', methods: ['GET'])]
    public function getEntreesByPeriod(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');

        if (!$debut || !$fin) {
            return new JsonResponse(['error' => 'Paramètres debut et fin requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dateDebut = new \DateTime($debut);
            $dateFin = new \DateTime($fin);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        $entrees = $this->statisticsService->getEntreesByPeriod($user, $dateDebut, $dateFin);

        return new JsonResponse([
            'entrees' => $entrees,
            'devise' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF'
        ]);
    }

    /**
     * Obtenir les sorties pour une période donnée
     */
    #[Route('/sorties', name: 'sorties', methods: ['GET'])]
    public function getSortiesByPeriod(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');

        if (!$debut || !$fin) {
            return new JsonResponse(['error' => 'Paramètres debut et fin requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dateDebut = new \DateTime($debut);
            $dateFin = new \DateTime($fin);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        $sorties = $this->statisticsService->getSortiesByPeriod($user, $dateDebut, $dateFin);

        return new JsonResponse([
            'sorties' => $sorties,
            'devise' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF'
        ]);
    }

    /**
     * Obtenir les entrées et sorties pour une période donnée
     */
    #[Route('/entrees-sorties', name: 'entrees_sorties', methods: ['GET'])]
    public function getEntreesSortiesByPeriod(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');

        if (!$debut || !$fin) {
            return new JsonResponse(['error' => 'Paramètres debut et fin requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dateDebut = new \DateTime($debut);
            $dateFin = new \DateTime($fin);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        $resultat = $this->statisticsService->getEntreesSortiesByPeriod($user, $dateDebut, $dateFin);

        return new JsonResponse([
            'periode' => $resultat['periode'],
            'entrees' => $resultat['entrees'],
            'sorties' => $resultat['sorties'],
            'solde_net' => $resultat['solde_net'],
            'solde_net_formatted' => $resultat['solde_net_formatted'],
            'devise' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF'
        ]);
    }

    private function getDateDebut(string $periode): \DateTime
    {
        $debut = new \DateTime();
        
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
        
        return $debut;
    }

    private function getPeriodeActuelle(string $periode): array
    {
        $fin = new \DateTime();
        $debut = clone $fin;
        
        switch ($periode) {
            case 'semaine':
                $debut->modify('monday this week');
                break;
            case 'mois':
                $debut->modify('first day of this month');
                break;
            case 'trimestre':
                $mois = (int) $fin->format('n');
                $trimestre = ceil($mois / 3);
                $debut->modify('first day of january');
                $debut->modify('+' . (($trimestre - 1) * 3) . ' months');
                break;
            default:
                $debut->modify('first day of this month');
        }
        
        return ['debut' => $debut, 'fin' => $fin];
    }

    private function getPeriodePrecedente(string $periode): array
    {
        $fin = new \DateTime();
        $debut = clone $fin;
        
        switch ($periode) {
            case 'semaine':
                $fin->modify('sunday last week');
                $debut->modify('monday last week');
                break;
            case 'mois':
                $fin->modify('last day of last month');
                $debut->modify('first day of last month');
                break;
            case 'trimestre':
                $mois = (int) $fin->format('n');
                $trimestre = ceil($mois / 3) - 1;
                if ($trimestre <= 0) {
                    $trimestre = 4;
                    $fin->modify('last day of december last year');
                } else {
                    $fin->modify('last day of ' . (($trimestre * 3)) . ' months ago');
                }
                $debut->modify('first day of ' . (($trimestre - 1) * 3 + 1) . ' months ago');
                break;
            default:
                $fin->modify('last day of last month');
                $debut->modify('first day of last month');
        }
        
        return ['debut' => $debut, 'fin' => $fin];
    }

    private function getPeriodeOffset(string $periode, int $offset): array
    {
        $fin = new \DateTime();
        $debut = clone $fin;
        
        switch ($periode) {
            case 'semaine':
                $fin->modify('-' . $offset . ' weeks');
                $debut->modify('-' . $offset . ' weeks');
                $debut->modify('monday this week');
                $fin->modify('sunday this week');
                break;
            case 'mois':
                $fin->modify('-' . $offset . ' months');
                $debut->modify('-' . $offset . ' months');
                $debut->modify('first day of this month');
                $fin->modify('last day of this month');
                break;
            case 'trimestre':
                $fin->modify('-' . ($offset * 3) . ' months');
                $debut->modify('-' . ($offset * 3) . ' months');
                $mois = (int) $fin->format('n');
                $trimestre = ceil($mois / 3);
                $debut->modify('first day of january');
                $debut->modify('+' . (($trimestre - 1) * 3) . ' months');
                $fin->modify('last day of ' . ($trimestre * 3) . ' months');
                break;
            default:
                $fin->modify('-' . $offset . ' months');
                $debut->modify('-' . $offset . ' months');
                $debut->modify('first day of this month');
                $fin->modify('last day of this month');
        }
        
        return ['debut' => $debut, 'fin' => $fin];
    }

    private function calculerVariation(float $actuel, float $precedent): float
    {
        if ($precedent == 0) {
            return $actuel > 0 ? 100 : 0;
        }
        return (($actuel - $precedent) / $precedent) * 100;
    }

    private function getTotalMois(User $user): float
    {
        $debut = new \DateTime('first day of this month');
        $fin = new \DateTime('last day of this month');
        
        // Utiliser le nouveau service de statistiques
        $statistics = $this->statisticsService->calculateGlobalStatistics($user, $debut, $fin);
        
        return $statistics['solde_net'];
    }

    private function getDonneesEvolution(User $user, string $periode, string $type): array
    {
        // Placeholder pour les données d'évolution
        // À implémenter selon les besoins spécifiques
        return [
            'message' => 'Graphique en cours de développement',
            'donnees' => []
        ];
    }

}
