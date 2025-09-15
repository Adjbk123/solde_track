<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Devise;
use App\Service\UserDeviseService;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/profile', name: 'api_profile_')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher,
        private UserDeviseService $userDeviseService,
        private PhotoUploadService $photoUploadService
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto() ? $this->photoUploadService->getPublicUrl($user->getPhoto()) : null,
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'devise' => [
                    'id' => $user->getDevise()?->getId(),
                    'code' => $user->getDevise()?->getCode(),
                    'nom' => $user->getDevise()?->getNom()
                ],
                'dateCreation' => $user->getDateCreation()?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour les champs modifiables
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenoms'])) {
            $user->setPrenoms($data['prenoms']);
        }
        if (isset($data['photo'])) {
            $user->setPhoto($data['photo']);
        }
        if (isset($data['dateNaissance'])) {
            $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        }
        if (isset($data['devise_id'])) {
            $devise = $this->entityManager->getRepository(Devise::class)->find($data['devise_id']);
            if ($devise) {
                $user->setDevise($devise);
            }
        }

        // Valider l'entité
        $errors = $this->validator->validate($user);
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
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'devise' => [
                    'id' => $user->getDevise()?->getId(),
                    'code' => $user->getDevise()?->getCode(),
                    'nom' => $user->getDevise()?->getNom()
                ]
            ]
        ]);
    }

    #[Route('/change-email', name: 'change_email', methods: ['POST'])]
    public function changeEmail(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['newEmail']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Nouvel email et mot de passe sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'error' => 'Mot de passe incorrect',
                'message' => 'Le mot de passe est incorrect'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le nouvel email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['newEmail']]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return new JsonResponse([
                'error' => 'Email existant',
                'message' => 'Cette adresse email est déjà utilisée'
            ], Response::HTTP_CONFLICT);
        }

        // Valider le format de l'email
        if (!filter_var($data['newEmail'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => 'Email invalide',
                'message' => 'Le format de l\'email est invalide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setEmail($data['newEmail']);

        // Valider l'entité
        $errors = $this->validator->validate($user);
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
            'message' => 'Email modifié avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms()
            ]
        ]);
    }

    #[Route('/delete-account', name: 'delete_account', methods: ['DELETE'])]
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['password']) || !isset($data['confirmation'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Mot de passe et confirmation sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'error' => 'Mot de passe incorrect',
                'message' => 'Le mot de passe est incorrect'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier la confirmation
        if ($data['confirmation'] !== 'SUPPRIMER MON COMPTE') {
            return new JsonResponse([
                'error' => 'Confirmation invalide',
                'message' => 'Veuillez taper exactement "SUPPRIMER MON COMPTE" pour confirmer'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Supprimer l'utilisateur (cascade supprimera automatiquement ses données)
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Compte supprimé avec succès'
        ]);
    }

    #[Route('/change-devise', name: 'change_devise', methods: ['POST'])]
    public function changeDevise(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['devise_id'])) {
            return new JsonResponse([
                'error' => 'Devise requise',
                'message' => 'L\'ID de la devise est requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $devise = $this->entityManager->getRepository(Devise::class)->find($data['devise_id']);
        if (!$devise) {
            return new JsonResponse([
                'error' => 'Devise non trouvée',
                'message' => 'La devise sélectionnée n\'existe pas'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->userDeviseService->changeUserDevise($user, $devise);

        return new JsonResponse([
            'message' => 'Devise modifiée avec succès',
            'devise' => [
                'id' => $devise->getId(),
                'code' => $devise->getCode(),
                'nom' => $devise->getNom()
            ]
        ]);
    }
}
