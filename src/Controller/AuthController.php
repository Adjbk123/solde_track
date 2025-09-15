<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DefaultCategoriesService;
use App\Service\DefaultDevisesService;
use App\Service\DefaultComptesService;
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

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private DefaultCategoriesService $defaultCategoriesService,
        private DefaultDevisesService $defaultDevisesService,
        private DefaultComptesService $defaultComptesService,
        private UserDeviseService $userDeviseService,
        private PhotoUploadService $photoUploadService
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['nom']) || !isset($data['prenoms']) || !isset($data['devise_id'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Email, mot de passe, nom, prénoms et devise sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Utilisateur existant',
                'message' => 'Un utilisateur avec cet email existe déjà'
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier que la devise existe
        $devise = $this->entityManager->getRepository(\App\Entity\Devise::class)->find($data['devise_id']);
        if (!$devise) {
            return new JsonResponse([
                'error' => 'Devise invalide',
                'message' => 'La devise sélectionnée n\'existe pas'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenoms($data['prenoms']);
        $user->setDevise($devise);
        
        // Champs optionnels
        if (isset($data['dateNaissance'])) {
            $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

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

        // Sauvegarder l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Créer les catégories par défaut pour le nouvel utilisateur
        $this->defaultCategoriesService->createDefaultCategoriesForUser($user);

        // Créer les comptes par défaut pour le nouvel utilisateur
        $this->defaultComptesService->createDefaultComptesForUser($user);

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'devise' => $user->getDevise()?->getCode()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Email et mot de passe sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'error' => 'Identifiants invalides',
                'message' => 'Email ou mot de passe incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Le JWT sera généré automatiquement par le bundle
        return new JsonResponse([
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d')
            ]
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse([
                'error' => 'Non authentifié',
                'message' => 'Token JWT invalide ou expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d')
            ]
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        // Le refresh token sera géré automatiquement par le bundle
        return new JsonResponse([
            'message' => 'Token rafraîchi avec succès'
        ]);
    }

    #[Route('/devises', name: 'devises', methods: ['GET'])]
    public function getDevises(): JsonResponse
    {
        $devises = $this->userDeviseService->getAllDevises();

        return new JsonResponse(['devises' => $devises]);
    }

    #[Route('/devises/popular', name: 'devises_popular', methods: ['GET'])]
    public function getPopularDevises(): JsonResponse
    {
        $devises = $this->userDeviseService->getPopularDevises();

        return new JsonResponse(['devises' => $devises]);
    }

    #[Route('/register-with-photo', name: 'register_with_photo', methods: ['POST'])]
    public function registerWithPhoto(Request $request): JsonResponse
    {
        // Récupérer les données du formulaire
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $nom = $request->request->get('nom');
        $prenoms = $request->request->get('prenoms');
        $deviseId = $request->request->get('devise_id');
        $dateNaissance = $request->request->get('dateNaissance');
        $uploadedFile = $request->files->get('photo');

        // Validation des données requises
        if (!$email || !$password || !$nom || !$prenoms || !$deviseId) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Email, mot de passe, nom, prénoms et devise sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Utilisateur existant',
                'message' => 'Un utilisateur avec cet email existe déjà'
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier que la devise existe
        $devise = $this->entityManager->getRepository(\App\Entity\Devise::class)->find($deviseId);
        if (!$devise) {
            return new JsonResponse([
                'error' => 'Devise invalide',
                'message' => 'La devise sélectionnée n\'existe pas'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenoms($prenoms);
        $user->setDevise($devise);
        
        // Champs optionnels
        if ($dateNaissance) {
            $user->setDateNaissance(new \DateTime($dateNaissance));
        }

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

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

        // Sauvegarder l'utilisateur d'abord pour avoir un ID
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Gérer l'upload de photo si fournie
        if ($uploadedFile) {
            try {
                // Valider le fichier
                $errors = $this->photoUploadService->validateFile($uploadedFile);
                if (!empty($errors)) {
                    // Supprimer l'utilisateur créé
                    $this->entityManager->remove($user);
                    $this->entityManager->flush();
                    
                    return new JsonResponse([
                        'error' => 'Photo invalide',
                        'messages' => $errors
                    ], Response::HTTP_BAD_REQUEST);
                }

                // Uploader la photo
                $fileName = $this->photoUploadService->upload($uploadedFile, $user->getId());
                
                // Redimensionner l'image
                $filePath = $this->photoUploadService->getFilePath($fileName);
                $this->photoUploadService->resizeImage($filePath, 300, 300);

                // Mettre à jour l'utilisateur avec la photo
                $user->setPhoto($fileName);
                $this->entityManager->flush();

            } catch (\Exception $e) {
                // Supprimer l'utilisateur créé en cas d'erreur
                $this->entityManager->remove($user);
                $this->entityManager->flush();
                
                return new JsonResponse([
                    'error' => 'Erreur lors de l\'upload de la photo',
                    'message' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // Créer les catégories par défaut pour le nouvel utilisateur
        $this->defaultCategoriesService->createDefaultCategoriesForUser($user);

        // Créer les comptes par défaut pour le nouvel utilisateur
        $this->defaultComptesService->createDefaultComptesForUser($user);

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto() ? $this->photoUploadService->getPublicUrl($user->getPhoto()) : null,
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'devise' => [
                    'id' => $user->getDevise()->getId(),
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Mot de passe actuel et nouveau mot de passe sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe actuel
        if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return new JsonResponse([
                'error' => 'Mot de passe incorrect',
                'message' => 'Le mot de passe actuel est incorrect'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le nouveau mot de passe
        if (strlen($data['newPassword']) < 6) {
            return new JsonResponse([
                'error' => 'Mot de passe trop court',
                'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que le nouveau mot de passe est différent de l'ancien
        if ($this->passwordHasher->isPasswordValid($user, $data['newPassword'])) {
            return new JsonResponse([
                'error' => 'Mot de passe identique',
                'message' => 'Le nouveau mot de passe doit être différent de l\'actuel'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Hasher et sauvegarder le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse([
                'error' => 'Email requis',
                'message' => 'L\'adresse email est requise'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        
        // Pour des raisons de sécurité, on ne révèle pas si l'email existe ou non
        if ($user) {
            // Ici, vous pourriez envoyer un email de réinitialisation
            // Pour l'instant, on retourne juste un message générique
            return new JsonResponse([
                'message' => 'Si cette adresse email existe dans notre système, vous recevrez un email de réinitialisation'
            ]);
        }

        // Même message pour ne pas révéler l'existence de l'email
        return new JsonResponse([
            'message' => 'Si cette adresse email existe dans notre système, vous recevrez un email de réinitialisation'
        ]);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['token']) || !isset($data['newPassword'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Email, token et nouveau mot de passe sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if (!$user) {
            return new JsonResponse([
                'error' => 'Utilisateur non trouvé',
                'message' => 'Aucun utilisateur trouvé avec cette adresse email'
            ], Response::HTTP_NOT_FOUND);
        }

        // Ici, vous devriez vérifier le token de réinitialisation
        // Pour l'instant, on simule une validation
        if ($data['token'] !== 'valid_reset_token') {
            return new JsonResponse([
                'error' => 'Token invalide',
                'message' => 'Le token de réinitialisation est invalide ou expiré'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le nouveau mot de passe
        if (strlen($data['newPassword']) < 6) {
            return new JsonResponse([
                'error' => 'Mot de passe trop court',
                'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Hasher et sauvegarder le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }
}
