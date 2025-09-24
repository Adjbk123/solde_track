<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\User;
use App\Service\UserDeviseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/projets', name: 'api_projets_')]
class ProjetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserDeviseService $userDeviseService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $search = $request->query->get('search');

        $projets = $user->getProjets();
        
        // Filtrer par recherche si fournie
        if ($search) {
            $projets = $projets->filter(function($projet) use ($search) {
                return stripos($projet->getNom(), $search) !== false || 
                       stripos($projet->getDescription() ?? '', $search) !== false;
            });
        }

        // Pagination
        $total = $projets->count();
        $offset = ($page - 1) * $limit;
        $projets = $projets->slice($offset, $limit);

        $data = [];
        foreach ($projets as $projet) {
            $data[] = $this->serializeProjet($projet, $user);
        }

        return new JsonResponse([
            'projets' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projetId = (int) $id;
        $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
        if (!$projet || $projet->getUser() !== $user) {
            return new JsonResponse(['error' => 'Projet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['projet' => $this->serializeProjet($projet, $user, true)]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Nom du projet requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $projet = new Projet();
        $projet->setUser($user);
        $projet->setNom($data['nom']);
        $projet->setDescription($data['description'] ?? null);
        $projet->setBudgetPrevu($data['budgetPrevu'] ?? null);

        $errors = $this->validator->validate($projet);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Données invalides',
                'messages' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Projet créé avec succès',
            'projet' => [
                'id' => $projet->getId(),
                'nom' => $projet->getNom(),
                'description' => $projet->getDescription(),
                'budgetPrevu' => $projet->getBudgetPrevu(),
                'dateCreation' => $projet->getDateCreation()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projetId = (int) $id;
        $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
        if (!$projet || $projet->getUser() !== $user) {
            return new JsonResponse(['error' => 'Projet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $projet->setNom($data['nom']);
        }
        if (isset($data['description'])) {
            $projet->setDescription($data['description']);
        }
        if (isset($data['budgetPrevu'])) {
            $projet->setBudgetPrevu($data['budgetPrevu']);
        }

        $errors = $this->validator->validate($projet);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Données invalides',
                'messages' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Projet mis à jour avec succès',
            'projet' => [
                'id' => $projet->getId(),
                'nom' => $projet->getNom(),
                'description' => $projet->getDescription(),
                'budgetPrevu' => $projet->getBudgetPrevu(),
                'dateCreation' => $projet->getDateCreation()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projetId = (int) $id;
        $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
        if (!$projet || $projet->getUser() !== $user) {
            return new JsonResponse(['error' => 'Projet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier s'il y a des mouvements associés
        if ($projet->getMouvements()->count() > 0) {
            return new JsonResponse([
                'error' => 'Impossible de supprimer',
                'message' => 'Ce projet contient des mouvements'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($projet);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Projet supprimé avec succès'
        ]);
    }

    #[Route('/{id}/statistiques', name: 'statistiques', methods: ['GET'])]
    public function getStatistiques(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projetId = (int) $id;
        $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
        if (!$projet || $projet->getUser() !== $user) {
            return new JsonResponse(['error' => 'Projet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $mouvements = $projet->getMouvements();
        $totalDepenses = 0;
        $totalEntrees = 0;
        $totalDettes = 0;
        $totalDons = 0;

        foreach ($mouvements as $mouvement) {
            $montant = (float) $mouvement->getMontantEffectif();
            switch ($mouvement->getType()) {
                case 'sortie':
                    $totalDepenses += $montant;
                    break;
                case 'entree':
                    $totalEntrees += $montant;
                    break;
                case 'dette':
                    $totalDettes += $montant;
                    break;
                case 'don':
                    $totalDons += $montant;
                    break;
            }
        }

        $budgetPrevu = $projet->getBudgetPrevu() ? (float) $projet->getBudgetPrevu() : 0;
        $soldeActuel = $totalEntrees - $totalDepenses;
        $pourcentageUtilise = $budgetPrevu > 0 ? ($totalDepenses / $budgetPrevu) * 100 : 0;

        return new JsonResponse([
            'projet' => $this->serializeProjet($projet, $user),
            'statistiques' => [
                'budgetPrevu' => $budgetPrevu,
                'budgetPrevuFormatted' => $budgetPrevu > 0 ? $this->userDeviseService->formatAmount($user, $budgetPrevu) : null,
                'totalDepenses' => $totalDepenses,
                'totalDepensesFormatted' => $this->userDeviseService->formatAmount($user, $totalDepenses),
                'totalEntrees' => $totalEntrees,
                'totalEntreesFormatted' => $this->userDeviseService->formatAmount($user, $totalEntrees),
                'totalDettes' => $totalDettes,
                'totalDettesFormatted' => $this->userDeviseService->formatAmount($user, $totalDettes),
                'totalDons' => $totalDons,
                'totalDonsFormatted' => $this->userDeviseService->formatAmount($user, $totalDons),
                'soldeActuel' => $soldeActuel,
                'soldeActuelFormatted' => $this->userDeviseService->formatAmount($user, $soldeActuel),
                'pourcentageUtilise' => round($pourcentageUtilise, 2),
                'nombreMouvements' => $mouvements->count(),
                'derniereActivite' => $mouvements->count() > 0 ? 
                    $mouvements->first()->getDate()->format('Y-m-d H:i:s') : null
            ]
        ]);
    }

    #[Route('/{id}/mouvements', name: 'mouvements', methods: ['GET'])]
    public function getMouvements(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projetId = (int) $id;
        $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
        if (!$projet || $projet->getUser() !== $user) {
            return new JsonResponse(['error' => 'Projet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $type = $request->query->get('type');

        $mouvements = $projet->getMouvements();

        // Filtrer par type si fourni
        if ($type) {
            $mouvements = $mouvements->filter(function($mouvement) use ($type) {
                return $mouvement->getType() === $type;
            });
        }

        // Trier par date décroissante
        $mouvementsArray = $mouvements->toArray();
        usort($mouvementsArray, function($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });

        // Pagination
        $total = count($mouvementsArray);
        $offset = ($page - 1) * $limit;
        $mouvementsPagines = array_slice($mouvementsArray, $offset, $limit);

        $data = [];
        foreach ($mouvementsPagines as $mouvement) {
            $data[] = [
                'id' => $mouvement->getId(),
                'type' => $mouvement->getType(),
                'montant' => $mouvement->getMontantEffectif(),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantEffectif()),
                'description' => $mouvement->getDescription(),
                'date' => $mouvement->getDate()->format('Y-m-d H:i:s'),
                'statut' => $mouvement->getStatut(),
                'categorie' => $mouvement->getCategorie() ? [
                    'id' => $mouvement->getCategorie()->getId(),
                    'nom' => $mouvement->getCategorie()->getNom()
                ] : null
            ];
        }

        return new JsonResponse([
            'mouvements' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    private function serializeProjet(Projet $projet, User $user, bool $detailed = false): array
    {
        $data = [
            'id' => $projet->getId(),
            'nom' => $projet->getNom(),
            'description' => $projet->getDescription(),
            'budgetPrevu' => $projet->getBudgetPrevu(),
            'budgetPrevuFormatted' => $projet->getBudgetPrevu() ? 
                $this->userDeviseService->formatAmount($user, (float) $projet->getBudgetPrevu()) : null,
            'dateCreation' => $projet->getDateCreation()->format('Y-m-d H:i:s'),
            'nombreMouvements' => $projet->getMouvements()->count()
        ];

        if ($detailed) {
            $mouvements = $projet->getMouvements();
            $totalDepenses = 0;
            $totalEntrees = 0;

            foreach ($mouvements as $mouvement) {
                $montant = (float) $mouvement->getMontantEffectif();
                if ($mouvement->getType() === 'sortie') {
                    $totalDepenses += $montant;
                } elseif ($mouvement->getType() === 'entree') {
                    $totalEntrees += $montant;
                }
            }

            $data['statistiques'] = [
                'totalDepenses' => $totalDepenses,
                'totalDepensesFormatted' => $this->userDeviseService->formatAmount($user, $totalDepenses),
                'totalEntrees' => $totalEntrees,
                'totalEntreesFormatted' => $this->userDeviseService->formatAmount($user, $totalEntrees),
                'soldeActuel' => $totalEntrees - $totalDepenses,
                'soldeActuelFormatted' => $this->userDeviseService->formatAmount($user, $totalEntrees - $totalDepenses)
            ];
        }

        return $data;
    }
}
