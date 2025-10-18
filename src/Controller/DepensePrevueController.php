<?php

namespace App\Controller;

use App\Entity\DepensePrevue;
use App\Entity\User;
use App\Service\DepensePrevueService;
use App\Service\ResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/depenses-prevues', name: 'api_depenses_prevues_')]
class DepensePrevueController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepensePrevueService $depensePrevueService
    ) {}

    /**
     * Créer une nouvelle dépense prévue
     */
    #[Route('', name: 'creer', methods: ['POST'])]
    public function creerDepensePrevue(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $donnees = json_decode($request->getContent(), true);
            $depensePrevue = $this->depensePrevueService->creerDepensePrevue($user, $donnees);

            return ResponseService::created([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue créée avec succès');

        } catch (\InvalidArgumentException $e) {
            return ResponseService::error($e->getMessage());

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Créer une dépense prévue simple (sans budget)
     */
    #[Route('/simple', name: 'creer_simple', methods: ['POST'])]
    public function creerDepensePrevueSimple(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $donnees = json_decode($request->getContent(), true);
            
            if (!isset($donnees['nom'])) {
                return ResponseService::error('Le nom est requis');
            }

            $depensePrevue = $this->depensePrevueService->creerDepensePrevueSimple(
                $user, 
                $donnees['nom'], 
                $donnees['description'] ?? null
            );

            return ResponseService::created([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue créée avec succès');

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Créer une dépense prévue avec budget estimé
     */
    #[Route('/estimee', name: 'creer_estimee', methods: ['POST'])]
    public function creerDepensePrevueEstimee(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $donnees = json_decode($request->getContent(), true);
            
            if (!isset($donnees['nom']) || !isset($donnees['budgetEstime'])) {
                return ResponseService::error('Le nom et le budget estimé sont requis');
            }

            $depensePrevue = $this->depensePrevueService->creerDepensePrevueEstimee(
                $user, 
                $donnees['nom'], 
                (float) $donnees['budgetEstime'],
                $donnees['description'] ?? null
            );

            return ResponseService::created([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue créée avec succès');

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Créer une dépense prévue avec budget certain
     */
    #[Route('/certaine', name: 'creer_certaine', methods: ['POST'])]
    public function creerDepensePrevueCertaine(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $donnees = json_decode($request->getContent(), true);
            
            if (!isset($donnees['nom']) || !isset($donnees['budgetCertain'])) {
                return ResponseService::error('Le nom et le budget certain sont requis');
            }

            $depensePrevue = $this->depensePrevueService->creerDepensePrevueCertaine(
                $user, 
                $donnees['nom'], 
                (float) $donnees['budgetCertain'],
                $donnees['description'] ?? null
            );

            return ResponseService::created([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue créée avec succès');

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Lister les dépenses prévues avec filtres
     */
    #[Route('', name: 'lister', methods: ['GET'])]
    public function listerDepensesPrevues(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $filtres = $this->extraireFiltres($request);
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);

            $result = $this->depensePrevueService->listDepensesPrevues($user, $filtres, $page, $limit);
            $serializedDepenses = array_map([$this, 'serialiserDepensePrevue'], $result['depensesPrevues']);

            return ResponseService::paginated(
                $serializedDepenses, 
                $page, 
                $limit, 
                $result['pagination']['total'], 
                'depensesPrevues'
            );

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Récupérer les détails d'une dépense prévue
     */
    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function detailsDepensePrevue(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            return ResponseService::success([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ]);

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Récupérer les mouvements d'une dépense prévue
     */
    #[Route('/{id}/mouvements', name: 'mouvements', methods: ['GET'])]
    public function getMouvements(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);

            $mouvementRepo = $this->entityManager->getRepository(\App\Entity\Mouvement::class);
            
            $qb = $mouvementRepo->createQueryBuilder('m')
                ->where('m.user = :user')
                ->andWhere('m.depensePrevue = :depensePrevue')
                ->setParameter('user', $user)
                ->setParameter('depensePrevue', $depensePrevue)
                ->orderBy('m.date', 'DESC');

            // Compter le total
            $total = count($qb->getQuery()->getResult());
            
            // Pagination
            $qb->setFirstResult(($page - 1) * $limit)
               ->setMaxResults($limit);

            $mouvements = $qb->getQuery()->getResult();

            return ResponseService::success([
                'mouvements' => array_map([$this, 'serialiserMouvement'], $mouvements),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ],
                'projet' => [
                    'id' => $depensePrevue->getId(),
                    'nom' => $depensePrevue->getNom(),
                    'budgetPrevu' => $depensePrevue->getBudgetPrevu(),
                    'montantDepense' => $depensePrevue->getMontantDepense(),
                    'montantRestant' => $depensePrevue->getMontantRestant(),
                ]
            ]);

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Ajouter un mouvement à une dépense prévue
     */
    #[Route('/{id}/mouvements', name: 'ajouter_mouvement', methods: ['POST'])]
    public function ajouterMouvement(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $donnees = json_decode($request->getContent(), true);
            
            // Validation des données requises
            if (!isset($donnees['type']) || !isset($donnees['montantTotal']) || !isset($donnees['categorie_id'])) {
                return ResponseService::error('Données manquantes: type, montantTotal et categorie_id sont requis');
            }

            // Ajouter l'ID de la dépense prévue aux données
            $donnees['depense_prevue_id'] = $id;

            // Créer le mouvement via le service unifié
            $mouvementUnifieService = $this->container->get(\App\Service\MouvementUnifieService::class);
            $mouvement = $mouvementUnifieService->creerMouvement($user, $donnees['type'], $donnees);

            return ResponseService::created([
                'mouvement' => $this->serialiserMouvement($mouvement),
                'projet' => [
                    'id' => $depensePrevue->getId(),
                    'nom' => $depensePrevue->getNom(),
                    'budgetPrevu' => $depensePrevue->getBudgetPrevu(),
                    'montantDepense' => $depensePrevue->getMontantDepense(),
                    'montantRestant' => $depensePrevue->getMontantRestant(),
                ]
            ], 'Mouvement ajouté au projet avec succès');

        } catch (\InvalidArgumentException $e) {
            return ResponseService::error($e->getMessage());
        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Modifier une dépense prévue
     */
    #[Route('/{id}', name: 'modifier', methods: ['PUT'])]
    public function modifierDepensePrevue(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $donnees = json_decode($request->getContent(), true);
            $depensePrevue = $this->depensePrevueService->mettreAJourDepensePrevue($depensePrevue, $donnees);

            return ResponseService::updated([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue modifiée avec succès');

        } catch (\InvalidArgumentException $e) {
            return ResponseService::error($e->getMessage());

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Supprimer une dépense prévue
     */
    #[Route('/{id}', name: 'supprimer', methods: ['DELETE'])]
    public function supprimerDepensePrevue(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $this->depensePrevueService->supprimerDepensePrevue($depensePrevue);

            return ResponseService::deleted('Dépense prévue supprimée avec succès');

        } catch (\InvalidArgumentException $e) {
            return ResponseService::error($e->getMessage());

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Démarrer une dépense prévue
     */
    #[Route('/{id}/demarrer', name: 'demarrer', methods: ['POST'])]
    public function demarrerDepensePrevue(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $depensePrevue = $this->depensePrevueService->demarrerDepensePrevue($depensePrevue);

            return ResponseService::updated([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue démarrée avec succès');

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Terminer une dépense prévue
     */
    #[Route('/{id}/terminer', name: 'terminer', methods: ['POST'])]
    public function terminerDepensePrevue(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $depensePrevue = $this->depensePrevueService->terminerDepensePrevue($depensePrevue);

            return ResponseService::updated([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue terminée avec succès');

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Annuler une dépense prévue
     */
    #[Route('/{id}/annuler', name: 'annuler', methods: ['POST'])]
    public function annulerDepensePrevue(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensePrevue = $this->depensePrevueService->getDepensePrevueById($id, $user);
            if (!$depensePrevue) {
                return ResponseService::notFound('Dépense prévue non trouvée');
            }

            $depensePrevue = $this->depensePrevueService->annulerDepensePrevue($depensePrevue);

            return ResponseService::updated([
                'depensePrevue' => $this->serialiserDepensePrevue($depensePrevue)
            ], 'Dépense prévue annulée avec succès');

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Récupérer les statistiques des dépenses prévues
     */
    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    public function getStatistiques(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $statistiques = $this->depensePrevueService->calculerStatistiques($user);
            return ResponseService::success(['statistiques' => $statistiques]);

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Récupérer les dépenses prévues nécessitant une attention
     */
    #[Route('/attention', name: 'attention', methods: ['GET'])]
    public function getDepensesPrevuesAttention(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            $depensesPrevues = $this->depensePrevueService->getDepensesPrevuesAttention($user);
            $serializedDepenses = array_map([$this, 'serialiserDepensePrevue'], $depensesPrevues);

            return ResponseService::success([
                'depensesPrevues' => $serializedDepenses
            ]);

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    // Méthodes privées

    private function serialiserMouvement(\App\Entity\Mouvement $mouvement): array
    {
        $user = $this->getUser();
        $deviseCode = 'XOF'; // Valeur par défaut
        
        if ($user instanceof \App\Entity\User && $user->getDevise()) {
            $deviseCode = $user->getDevise()->getCode();
        }

        return [
            'id' => $mouvement->getId(),
            'type' => $mouvement->getType(),
            'typeLabel' => $mouvement->getTypeLabel(),
            'montantEffectif' => $mouvement->getMontantEffectif(),
            'montantEffectifFormatted' => number_format((float) $mouvement->getMontantEffectif(), 0, ',', ' ') . ' ' . $deviseCode,
            'description' => $mouvement->getDescription(),
            'date' => $mouvement->getDate()->format('Y-m-d'),
            'dateFormatted' => $mouvement->getDate()->format('d/m/Y'),
            'categorie' => $mouvement->getCategorie() ? [
                'id' => $mouvement->getCategorie()->getId(),
                'nom' => $mouvement->getCategorie()->getNom(),
            ] : null,
            'compte' => $mouvement->getCompte() ? [
                'id' => $mouvement->getCompte()->getId(),
                'nom' => $mouvement->getCompte()->getNom(),
            ] : null,
            'statut' => $mouvement->getStatut(),
            'statutLabel' => $mouvement->getStatutLabel(),
        ];
    }

    private function extraireFiltres(Request $request): array
    {
        $filtres = [];

        if ($request->query->has('statut')) {
            $filtres['statut'] = $request->query->get('statut');
        }
        if ($request->query->has('typeBudget')) {
            $filtres['typeBudget'] = $request->query->get('typeBudget');
        }
        if ($request->query->has('budgetDepasse')) {
            $filtres['budgetDepasse'] = filter_var($request->query->get('budgetDepasse'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->query->has('enRetard')) {
            $filtres['enRetard'] = filter_var($request->query->get('enRetard'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->query->has('hasBudget')) {
            $filtres['hasBudget'] = filter_var($request->query->get('hasBudget'), FILTER_VALIDATE_BOOLEAN);
        }

        return $filtres;
    }

    private function serialiserDepensePrevue(DepensePrevue $depensePrevue): array
    {
        $deviseCode = $depensePrevue->getUser()->getDevise() ? $depensePrevue->getUser()->getDevise()->getCode() : 'XOF';

        return [
            'id' => $depensePrevue->getId(),
            'nom' => $depensePrevue->getNom(),
            'description' => $depensePrevue->getDescription(),
            'budgetPrevu' => $depensePrevue->getBudgetPrevu(),
            'budgetPrevuFormatted' => $depensePrevue->getBudgetPrevu() ? 
                number_format((float) $depensePrevue->getBudgetPrevu(), 0, ',', ' ') . ' ' . $deviseCode : null,
            'typeBudget' => $depensePrevue->getTypeBudget(),
            'typeBudgetLabel' => $depensePrevue->getTypeBudgetLabel(),
            'descriptionBudget' => $depensePrevue->getDescriptionBudget(),
            'montantDepense' => $depensePrevue->getMontantDepense(),
            'montantDepenseFormatted' => number_format((float) $depensePrevue->getMontantDepense(), 0, ',', ' ') . ' ' . $deviseCode,
            'montantRestant' => $depensePrevue->getMontantRestant(),
            'montantRestantFormatted' => number_format($depensePrevue->getMontantRestant(), 0, ',', ' ') . ' ' . $deviseCode,
            'pourcentageAvancement' => $depensePrevue->getPourcentageAvancement(),
            'statut' => $depensePrevue->getStatut(),
            'statutLabel' => $depensePrevue->getStatutLabel(),
            'dateDebutPrevue' => $depensePrevue->getDateDebutPrevue()?->format('Y-m-d'),
            'dateFinPrevue' => $depensePrevue->getDateFinPrevue()?->format('Y-m-d'),
            'dateDebutReelle' => $depensePrevue->getDateDebutReelle()?->format('Y-m-d'),
            'dateFinReelle' => $depensePrevue->getDateFinReelle()?->format('Y-m-d'),
            'dateCreation' => $depensePrevue->getDateCreation()->format('Y-m-d H:i:s'),
            'dateModification' => $depensePrevue->getUpdatedAt()->format('Y-m-d H:i:s'),
            'isBudgetDepasse' => $depensePrevue->isBudgetDepasse(),
            'isEnRetard' => $depensePrevue->isEnRetard(),
            'hasBudgetDefini' => $depensePrevue->hasBudgetDefini(),
            'nombreMouvements' => $depensePrevue->getMouvements()->count(),
            'createdAt' => $depensePrevue->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $depensePrevue->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
