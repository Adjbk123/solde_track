<?php

namespace App\Controller;

use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories', name: 'api_categories_')]
class CategorieController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type');
        
        if ($type) {
            $categories = $this->entityManager->getRepository(Categorie::class)
                ->findByUserAndType($user, $type);
        } else {
            $categories = $this->entityManager->getRepository(Categorie::class)
                ->findByUser($user);
        }

        $data = [];
        foreach ($categories as $categorie) {
            $data[] = [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNom(),
                'type' => $categorie->getType(),
                'typeLabel' => $categorie->getTypeLabel(),
                'dateCreation' => $categorie->getDateCreation()->format('Y-m-d H:i:s'),
                'nombreMouvements' => $categorie->getMouvements()->count()
            ];
        }

        return new JsonResponse(['categories' => $data]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || !isset($data['type'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Nom et type sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = new Categorie();
        $categorie->setUser($user);
        $categorie->setNom($data['nom']);
        $categorie->setType($data['type']);

        $errors = $this->validator->validate($categorie);
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

        $this->entityManager->persist($categorie);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Catégorie créée avec succès',
            'categorie' => [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNom(),
                'type' => $categorie->getType(),
                'typeLabel' => $categorie->getTypeLabel(),
                'dateCreation' => $categorie->getDateCreation()->format('Y-m-d H:i:s')
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

        $categorieId = (int) $id;
        $categorie = $this->entityManager->getRepository(Categorie::class)->find($categorieId);
        if (!$categorie || $categorie->getUser() !== $user) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $categorie->setNom($data['nom']);
        }
        if (isset($data['type'])) {
            $categorie->setType($data['type']);
        }

        $errors = $this->validator->validate($categorie);
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
            'message' => 'Catégorie mise à jour avec succès',
            'categorie' => [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNom(),
                'type' => $categorie->getType(),
                'typeLabel' => $categorie->getTypeLabel(),
                'dateCreation' => $categorie->getDateCreation()->format('Y-m-d H:i:s')
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

        $categorieId = (int) $id;
        $categorie = $this->entityManager->getRepository(Categorie::class)->find($categorieId);
        if (!$categorie || $categorie->getUser() !== $user) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier s'il y a des mouvements associés
        if ($categorie->getMouvements()->count() > 0) {
            return new JsonResponse([
                'error' => 'Impossible de supprimer',
                'message' => 'Cette catégorie est utilisée par des mouvements'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($categorie);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }
}
