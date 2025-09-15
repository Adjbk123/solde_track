<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Mouvement;
use App\Entity\Dette;
use App\Service\UserDeviseService;
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
        private UserDeviseService $userDeviseService
    ) {}

    #[Route('/resume', name: 'resume', methods: ['GET'])]
    public function getResume(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'semaine'); // semaine, mois, trimestre
        $debut = $this->getDateDebut($periode);
        $fin = new \DateTime();

        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);

        // Calculer les statistiques
        $entrees = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_ENTREE, $debut, $fin);
        $depenses = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DEPENSE, $debut, $fin);
        $dettesAPayer = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_PAYER, $debut, $fin);
        $dettesARecevoir = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_RECEVOIR, $debut, $fin);
        $dons = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DON, $debut, $fin);

        $soldeTotal = $entrees + $dettesARecevoir - $depenses - $dettesAPayer - $dons;

        // Calculer les pourcentages de variation (comparaison avec période précédente)
        $periodePrecedente = $this->getPeriodePrecedente($periode);
        $entreesPrecedentes = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_ENTREE, $periodePrecedente['debut'], $periodePrecedente['fin']);
        $depensesPrecedentes = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DEPENSE, $periodePrecedente['debut'], $periodePrecedente['fin']);

        $variationEntrees = $this->calculerVariation($entrees, $entreesPrecedentes);
        $variationDepenses = $this->calculerVariation($depenses, $depensesPrecedentes);

        return new JsonResponse([
            'periode' => $periode,
            'entrees' => [
                'montant' => number_format($entrees, 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $entrees),
                'variation' => $variationEntrees,
                'variationFormatted' => ($variationEntrees >= 0 ? '+' : '') . number_format($variationEntrees, 1) . '%'
            ],
            'depenses' => [
                'montant' => number_format($depenses, 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $depenses),
                'variation' => $variationDepenses,
                'variationFormatted' => ($variationDepenses >= 0 ? '+' : '') . number_format($variationDepenses, 1) . '%'
            ],
            'soldeTotal' => [
                'montant' => number_format($soldeTotal, 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $soldeTotal)
            ],
            'ceMois' => [
                'montant' => number_format($this->getTotalMois($user), 2, '.', ''),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, $this->getTotalMois($user))
            ]
        ]);
    }

    #[Route('/evolution-depenses', name: 'evolution_depenses', methods: ['GET'])]
    public function getEvolutionDepenses(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $periode = $request->query->get('periode', 'semaine');
        $type = $request->query->get('type', 'depenses'); // depenses, entrees, solde

        $donnees = $this->getDonneesEvolution($user, $periode, $type);

        return new JsonResponse([
            'periode' => $periode,
            'type' => $type,
            'donnees' => $donnees,
            'message' => 'Graphique en cours de développement'
        ]);
    }

    #[Route('/depenses-par-categorie', name: 'depenses_par_categorie', methods: ['GET'])]
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
        
        // Période actuelle
        $actuelle = $this->getPeriodeActuelle($periode);
        $actuelle['entrees'] = $this->entityManager->getRepository(Mouvement::class)->getTotalByType($user, Mouvement::TYPE_ENTREE, $actuelle['debut'], $actuelle['fin']);
        $actuelle['depenses'] = $this->entityManager->getRepository(Mouvement::class)->getTotalByType($user, Mouvement::TYPE_DEPENSE, $actuelle['debut'], $actuelle['fin']);
        
        // Période précédente
        $precedente = $this->getPeriodePrecedente($periode);
        $precedente['entrees'] = $this->entityManager->getRepository(Mouvement::class)->getTotalByType($user, Mouvement::TYPE_ENTREE, $precedente['debut'], $precedente['fin']);
        $precedente['depenses'] = $this->entityManager->getRepository(Mouvement::class)->getTotalByType($user, Mouvement::TYPE_DEPENSE, $precedente['debut'], $precedente['fin']);

        return new JsonResponse([
            'periode' => $periode,
            'actuelle' => [
                'debut' => $actuelle['debut']->format('Y-m-d'),
                'fin' => $actuelle['fin']->format('Y-m-d'),
                'entrees' => [
                    'montant' => number_format($actuelle['entrees'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $actuelle['entrees'])
                ],
                'depenses' => [
                    'montant' => number_format($actuelle['depenses'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $actuelle['depenses'])
                ]
            ],
            'precedente' => [
                'debut' => $precedente['debut']->format('Y-m-d'),
                'fin' => $precedente['fin']->format('Y-m-d'),
                'entrees' => [
                    'montant' => number_format($precedente['entrees'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $precedente['entrees'])
                ],
                'depenses' => [
                    'montant' => number_format($precedente['depenses'], 2, '.', ''),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, $precedente['depenses'])
                ]
            ],
            'variations' => [
                'entrees' => $this->calculerVariation($actuelle['entrees'], $precedente['entrees']),
                'depenses' => $this->calculerVariation($actuelle['depenses'], $precedente['depenses'])
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

        $tendances = [];
        for ($i = $nbPeriodes - 1; $i >= 0; $i--) {
            $periodeData = $this->getPeriodeOffset($periode, $i);
            $entrees = $this->entityManager->getRepository(Mouvement::class)->getTotalByType($user, Mouvement::TYPE_ENTREE, $periodeData['debut'], $periodeData['fin']);
            $depenses = $this->entityManager->getRepository(Mouvement::class)->getTotalByType($user, Mouvement::TYPE_DEPENSE, $periodeData['debut'], $periodeData['fin']);
            
            $tendances[] = [
                'periode' => $periodeData['debut']->format('Y-m-d') . ' / ' . $periodeData['fin']->format('Y-m-d'),
                'entrees' => number_format($entrees, 2, '.', ''),
                'depenses' => number_format($depenses, 2, '.', ''),
                'solde' => number_format($entrees - $depenses, 2, '.', '')
            ];
        }

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
        
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        $entrees = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_ENTREE, $debut, $fin);
        $depenses = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DEPENSE, $debut, $fin);
        
        return $entrees - $depenses;
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
