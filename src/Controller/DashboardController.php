<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Entity\Dette;
use App\Entity\User;
use App\Entity\Compte;
use App\Entity\Transfert;
use App\Service\UserDeviseService;
use App\Service\StatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/dashboard', name: 'api_dashboard_')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserDeviseService $userDeviseService,
        private StatisticsService $statisticsService
    ) {}

    #[Route('/solde', name: 'solde', methods: ['GET'])]
    public function getSolde(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        // Solde total
        $soldeTotal = $mouvementRepo->getSoldeTotal($user);

        // Statistiques par type
        $stats = [];
        foreach (Mouvement::TYPES as $type => $label) {
            $mouvements = $mouvementRepo->findByUserAndType($user, $type);
            $total = 0;
            $count = count($mouvements);
            
            foreach ($mouvements as $mouvement) {
                $total += (float) $mouvement->getMontantEffectif();
            }
            
            $stats[$type] = [
                'label' => $label,
                'count' => $count,
                'total' => number_format($total, 2, '.', '')
            ];
        }

        // Dettes en retard
        $dettesEnRetard = $this->entityManager->getRepository(Dette::class)->findEnRetard($user);
        $totalDettesEnRetard = 0;
        foreach ($dettesEnRetard as $dette) {
            $totalDettesEnRetard += (float) $dette->getMontantRest();
        }

        return new JsonResponse([
            'soldeTotal' => number_format($soldeTotal, 2, '.', ''),
            'soldeTotalFormatted' => $this->userDeviseService->formatAmount($user, $soldeTotal),
            'statistiques' => $stats,
            'dettesEnRetard' => [
                'count' => count($dettesEnRetard),
                'total' => number_format($totalDettesEnRetard, 2, '.', ''),
                'totalFormatted' => $this->userDeviseService->formatAmount($user, $totalDettesEnRetard)
            ],
            'devise' => [
                'code' => $this->userDeviseService->getUserDeviseCode($user),
                'nom' => $this->userDeviseService->getUserDeviseName($user)
            ]
        ]);
    }

    #[Route('/projets/soldes', name: 'projets_soldes', methods: ['GET'])]
    public function getProjetsSoldes(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projets = $user->getProjets();
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        $projetsSoldes = [];
        foreach ($projets as $projet) {
            $solde = $mouvementRepo->getSoldeProjet($user, $projet);
            $mouvements = $mouvementRepo->createQueryBuilder('m')
                ->where('m.user = :user')
                ->andWhere('m.projet = :projet')
                ->setParameter('user', $user)
                ->setParameter('projet', $projet)
                ->getQuery()
                ->getResult();

            $totalDepenses = 0;
            $totalEntrees = 0;
            
            foreach ($mouvements as $mouvement) {
                $montant = (float) $mouvement->getMontantEffectif();
                if (in_array($mouvement->getType(), [Mouvement::TYPE_ENTREE, Mouvement::TYPE_DETTE_A_RECEVOIR])) {
                    $totalEntrees += $montant;
                } else {
                    $totalDepenses += $montant;
                }
            }

            $projetsSoldes[] = [
                'id' => $projet->getId(),
                'nom' => $projet->getNom(),
                'description' => $projet->getDescription(),
                'budgetPrevu' => $projet->getBudgetPrevu(),
                'solde' => number_format($solde, 2, '.', ''),
                'totalDepenses' => number_format($totalDepenses, 2, '.', ''),
                'totalEntrees' => number_format($totalEntrees, 2, '.', ''),
                'nombreMouvements' => count($mouvements)
            ];
        }

        return new JsonResponse([
            'projets' => $projetsSoldes
        ]);
    }

    #[Route('/historique', name: 'historique', methods: ['GET'])]
    public function getHistorique(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');
        $type = $request->query->get('type');

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.date', 'DESC');

        if ($debut) {
            $qb->andWhere('m.date >= :debut')->setParameter('debut', new \DateTime($debut));
        }
        if ($fin) {
            $qb->andWhere('m.date <= :fin')->setParameter('fin', new \DateTime($fin));
        }
        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        $mouvements = $qb->getQuery()->getResult();

        $historique = [];
        foreach ($mouvements as $mouvement) {
            $historique[] = [
                'id' => $mouvement->getId(),
                'type' => $mouvement->getType(),
                'typeLabel' => $mouvement->getTypeLabel(),
                'montant' => $mouvement->getMontantEffectif(),
                'date' => $mouvement->getDate()->format('Y-m-d H:i:s'),
                'description' => $mouvement->getDescription(),
                'categorie' => $mouvement->getCategorie()->getNom(),
                'projet' => $mouvement->getProjet()?->getNom(),
                'contact' => $mouvement->getContact()?->getNom()
            ];
        }

        return new JsonResponse([
            'historique' => $historique,
            'total' => count($historique)
        ]);
    }

    #[Route('/dettes/retard', name: 'dettes_retard', methods: ['GET'])]
    public function getDettesEnRetard(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $dettesEnRetard = $this->entityManager->getRepository(Dette::class)->findEnRetard($user);
        
        $dettes = [];
        foreach ($dettesEnRetard as $dette) {
            $dettes[] = [
                'id' => $dette->getId(),
                'montantTotal' => $dette->getMontantTotal(),
                'montantRest' => $dette->getMontantRest(),
                'echeance' => $dette->getEcheance()->format('Y-m-d'),
                'taux' => $dette->getTaux(),
                'montantInterets' => $dette->getMontantInterets(),
                'description' => $dette->getDescription(),
                'contact' => $dette->getContact()?->getNom(),
                'joursRetard' => $dette->getEcheance()->diff(new \DateTime())->days
            ];
        }

        return new JsonResponse([
            'dettes' => $dettes,
            'total' => count($dettes)
        ]);
    }

    #[Route('/comptes/soldes', name: 'comptes_soldes', methods: ['GET'])]
    public function getComptesSoldes(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $comptes = $user->getComptes();
        $comptesSoldes = [];
        $soldeTotal = 0;

        foreach ($comptes as $compte) {
            $soldeActuel = (float) $compte->getSoldeActuel();
            $soldeTotal += $soldeActuel;

            $comptesSoldes[] = [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'type' => $compte->getType(),
                'typeLabel' => $compte->getTypeLabel(),
                'soldeActuel' => $compte->getSoldeActuel(),
                'soldeActuelFormatted' => $this->userDeviseService->formatAmount($user, $soldeActuel),
                'soldeInitial' => $compte->getSoldeInitial(),
                'soldeInitialFormatted' => $this->userDeviseService->formatAmount($user, (float) $compte->getSoldeInitial()),
                'devise' => [
                    'id' => $compte->getDevise()->getId(),
                    'code' => $compte->getDevise()->getCode(),
                    'nom' => $compte->getDevise()->getNom()
                ],
                'dateCreation' => $compte->getDateCreation()->format('Y-m-d H:i:s'),
                'dateModification' => $compte->getDateModification()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'comptes' => $comptesSoldes,
            'soldeTotal' => number_format($soldeTotal, 2, '.', ''),
            'soldeTotalFormatted' => $this->userDeviseService->formatAmount($user, $soldeTotal),
            'nombreComptes' => count($comptes)
        ]);
    }

    #[Route('/transferts/recents', name: 'transferts_recents', methods: ['GET'])]
    public function getTransfertsRecents(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = (int) $request->query->get('limit', 5);

        $transferts = $this->entityManager->getRepository(Transfert::class)
            ->findBy(['user' => $user], ['date' => 'DESC'], $limit);

        $transfertsData = [];
        foreach ($transferts as $transfert) {
            $transfertsData[] = [
                'id' => $transfert->getId(),
                'montant' => $transfert->getMontant(),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $transfert->getMontant()),
                'date' => $transfert->getDate()->format('Y-m-d H:i:s'),
                'note' => $transfert->getNote(),
                'annule' => $transfert->isAnnule(),
                'compteSource' => [
                    'id' => $transfert->getCompteSource()->getId(),
                    'nom' => $transfert->getCompteSource()->getNom()
                ],
                'compteDestination' => [
                    'id' => $transfert->getCompteDestination()->getId(),
                    'nom' => $transfert->getCompteDestination()->getNom()
                ],
                'devise' => [
                    'id' => $transfert->getDevise()->getId(),
                    'code' => $transfert->getDevise()->getCode(),
                    'nom' => $transfert->getDevise()->getNom()
                ]
            ];
        }

        return new JsonResponse([
            'transferts' => $transfertsData,
            'total' => count($transfertsData)
        ]);
    }

    #[Route('/resume', name: 'resume', methods: ['GET'])]
    public function getResume(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        $compteRepo = $this->entityManager->getRepository(Compte::class);
        $transfertRepo = $this->entityManager->getRepository(Transfert::class);

        // Solde total des mouvements
        $soldeTotal = $mouvementRepo->getSoldeTotal($user);

        // Solde total des comptes
        $comptes = $user->getComptes();
        $soldeComptes = 0;
        foreach ($comptes as $compte) {
            $soldeComptes += (float) $compte->getSoldeActuel();
        }

        // Dettes en retard
        $dettesEnRetard = $this->entityManager->getRepository(Dette::class)->findEnRetard($user);
        $totalDettesEnRetard = 0;
        foreach ($dettesEnRetard as $dette) {
            $totalDettesEnRetard += (float) $dette->getMontantRest();
        }

        // Transferts récents
        $transfertsRecents = $transfertRepo->findBy(['user' => $user], ['date' => 'DESC'], 3);

        // Mouvements récents
        $mouvementsRecents = $mouvementRepo->findBy(['user' => $user], ['date' => 'DESC'], 5);

        // Statistiques par type
        $stats = [];
        foreach (Mouvement::TYPES as $type => $label) {
            $mouvements = $mouvementRepo->findByUserAndType($user, $type);
            $total = 0;
            $count = count($mouvements);
            
            foreach ($mouvements as $mouvement) {
                $total += (float) $mouvement->getMontantEffectif();
            }
            
            $stats[$type] = [
                'label' => $label,
                'count' => $count,
                'total' => number_format($total, 2, '.', ''),
                'totalFormatted' => $this->userDeviseService->formatAmount($user, $total)
            ];
        }

        return new JsonResponse([
            'soldeTotal' => number_format($soldeTotal, 2, '.', ''),
            'soldeTotalFormatted' => $this->userDeviseService->formatAmount($user, $soldeTotal),
            'soldeComptes' => number_format($soldeComptes, 2, '.', ''),
            'soldeComptesFormatted' => $this->userDeviseService->formatAmount($user, $soldeComptes),
            'dettesEnRetard' => [
                'count' => count($dettesEnRetard),
                'total' => number_format($totalDettesEnRetard, 2, '.', ''),
                'totalFormatted' => $this->userDeviseService->formatAmount($user, $totalDettesEnRetard)
            ],
            'statistiques' => $stats,
            'transfertsRecents' => array_map(function($transfert) use ($user) {
                return [
                    'id' => $transfert->getId(),
                    'montant' => $transfert->getMontant(),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $transfert->getMontant()),
                    'date' => $transfert->getDate()->format('Y-m-d H:i:s'),
                    'compteSource' => $transfert->getCompteSource()->getNom(),
                    'compteDestination' => $transfert->getCompteDestination()->getNom()
                ];
            }, $transfertsRecents),
            'mouvementsRecents' => array_map(function($mouvement) use ($user) {
                return [
                    'id' => $mouvement->getId(),
                    'type' => $mouvement->getType(),
                    'typeLabel' => $mouvement->getTypeLabel(),
                    'montant' => $mouvement->getMontantEffectif(),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantEffectif()),
                    'date' => $mouvement->getDate()->format('Y-m-d H:i:s'),
                    'description' => $mouvement->getDescription(),
                    'categorie' => $mouvement->getCategorie()->getNom()
                ];
            }, $mouvementsRecents),
            'devise' => [
                'code' => $this->userDeviseService->getUserDeviseCode($user),
                'nom' => $this->userDeviseService->getUserDeviseName($user)
            ]
        ]);
    }

    #[Route('/statistiques/periodes', name: 'statistiques_periodes', methods: ['GET'])]
    public function getStatistiquesPeriodes(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');

        if (!$debut || !$fin) {
            return new JsonResponse(['error' => 'Dates de début et fin requises'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.date >= :debut')
            ->andWhere('m.date <= :fin')
            ->setParameter('user', $user)
            ->setParameter('debut', new \DateTime($debut))
            ->setParameter('fin', new \DateTime($fin));

        $mouvements = $qb->getQuery()->getResult();

        // Utiliser le nouveau service de statistiques
        $statistics = $this->statisticsService->calculateGlobalStatistics($user, new \DateTime($debut), new \DateTime($fin));
        $statisticsByType = $this->statisticsService->calculateStatisticsByMovementType($user, new \DateTime($debut), new \DateTime($fin));

        // Compter les mouvements par type
        $counts = [];
        foreach ($mouvements as $mouvement) {
            $type = $mouvement->getType();
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
            $counts[$type]++;
        }

        // Construire les statistiques finales
        $stats = [];
        foreach ($statisticsByType as $type => $data) {
            $stats[$type] = [
                'count' => $counts[$type] ?? 0,
                'total' => $data['total'],
                'totalFormatted' => $this->userDeviseService->formatAmount($user, $data['total'])
            ];
        }

        $soldeNet = $statistics['solde_net'];

        return new JsonResponse([
            'periode' => [
                'debut' => $debut,
                'fin' => $fin
            ],
            'statistiques' => $stats,
            'soldeNet' => number_format($soldeNet, 2, '.', ''),
            'soldeNetFormatted' => $this->userDeviseService->formatAmount($user, $soldeNet),
            'totalMouvements' => count($mouvements)
        ]);
    }
}
