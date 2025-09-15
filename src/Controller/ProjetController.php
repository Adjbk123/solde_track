<?php

namespace App\Controller;

use App\Entity\Projet;
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
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projets = $user->getProjets();
        $data = [];
        
        foreach ($projets as $projet) {
            $data[] = [
                'id' => $projet->getId(),
                'nom' => $projet->getNom(),
                'description' => $projet->getDescription(),
                'budgetPrevu' => $projet->getBudgetPrevu(),
                'dateCreation' => $projet->getDateCreation()->format('Y-m-d H:i:s'),
                'nombreMouvements' => $projet->getMouvements()->count()
            ];
        }

        return new JsonResponse(['projets' => $data]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
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
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projet = $this->entityManager->getRepository(Projet::class)->find($id);
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
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projet = $this->entityManager->getRepository(Projet::class)->find($id);
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
}
