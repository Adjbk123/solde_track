<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DefaultCategoriesService;
use App\Service\DefaultDevisesService;
use App\Service\DefaultComptesService;
use App\Service\UserDeviseService;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
        private PhotoUploadService $photoUploadService,
        private JWTTokenManagerInterface $jwtManager
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

        // Générer le token JWT pour connecter automatiquement l'utilisateur
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Inscription réussie et connexion automatique',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'dateCreation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                'devise' => [
                    'id' => $user->getDevise()->getId(),
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ]
            ],
            'setup' => [
                'categories_created' => true,
                'comptes_created' => true,
                'message' => 'Catégories et comptes par défaut créés automatiquement'
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

        // Générer le token JWT
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'dateCreation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                'devise' => [
                    'id' => $user->getDevise()->getId(),
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ]
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

        // Construire l'URL complète de la photo si elle existe
        $photoUrl = null;
        if ($user->getPhoto()) {
      
            $photoUrl = $user->getPhoto();
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'photo' => $photoUrl,
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'dateCreation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                'devise' => [
                    'id' => $user->getDevise()?->getId(),
                    'code' => $user->getDevise()?->getCode(),
                    'nom' => $user->getDevise()?->getNom()
                ]
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

                // Mettre à jour l'utilisateur avec le chemin complet
                $fullPath = '/uploads/profils/' . $fileName;
                $user->setPhoto($fullPath);
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
                'photo' => $user->getPhoto(),
                'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                'dateCreation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                'devise' => [
                    'id' => $user->getDevise()->getId(),
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ]
            ],
            'setup' => [
                'categories_created' => true,
                'comptes_created' => true,
                'message' => 'Catégories et comptes par défaut créés automatiquement'
            ]
        ], Response::HTTP_CREATED);
    }

    // ===== ENDPOINTS PHOTO =====
    
    #[Route('/photo/upload', name: 'photo_upload', methods: ['POST'])]
    public function uploadPhoto(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $uploadedFile = $request->files->get('photo');
        
        if (!$uploadedFile) {
            return new JsonResponse([
                'error' => 'Aucun fichier fourni',
                'message' => 'Veuillez sélectionner une photo'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le fichier
        $errors = $this->photoUploadService->validateFile($uploadedFile);
        if (!empty($errors)) {
            return new JsonResponse([
                'error' => 'Fichier invalide',
                'messages' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Supprimer l'ancienne photo si elle existe
            if ($user->getPhoto()) {
                $oldPhotoPath = $user->getPhoto();
                $oldFileName = basename($oldPhotoPath);
                $this->photoUploadService->delete($oldFileName);
            }

            // Uploader la nouvelle photo
            $fileName = $this->photoUploadService->upload($uploadedFile, $user->getId());
            
            // Redimensionner l'image
            $filePath = $this->photoUploadService->getFilePath($fileName);
            $this->photoUploadService->resizeImage($filePath, 300, 300);

            // Mettre à jour l'utilisateur avec le chemin complet
            $fullPath = '/uploads/profils/' . $fileName;
            $user->setPhoto($fullPath);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Photo uploadée avec succès',
                'photo' => [
                    'filename' => $fileName,
                    'url' => $fullPath,
                    'full_path' => $fullPath
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'upload',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/photo/delete', name: 'photo_delete', methods: ['DELETE'])]
    public function deletePhoto(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getPhoto()) {
            return new JsonResponse([
                'error' => 'Aucune photo',
                'message' => 'Aucune photo de profil à supprimer'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Extraire le nom de fichier du chemin complet
            $photoPath = $user->getPhoto();
            $fileName = basename($photoPath);
            
            // Supprimer le fichier
            $deleted = $this->photoUploadService->delete($fileName);
            
            if ($deleted) {
                // Mettre à jour l'utilisateur
                $user->setPhoto(null);
                $this->entityManager->flush();

                return new JsonResponse([
                    'message' => 'Photo supprimée avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Erreur lors de la suppression',
                    'message' => 'Impossible de supprimer le fichier'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/photo/info', name: 'photo_info', methods: ['GET'])]
    public function getPhotoInfo(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $photoInfo = null;
        if ($user->getPhoto()) {
            $filePath = $this->photoUploadService->getFilePath($user->getPhoto());
            if (file_exists($filePath)) {
                $photoInfo = [
                    'filename' => $user->getPhoto(),
                    'url' => $user->getPhoto(),
                    'size' => filesize($filePath),
                    'lastModified' => filemtime($filePath)
                ];
            }
        }

        return new JsonResponse([
            'hasPhoto' => !is_null($user->getPhoto()),
            'photo' => $photoInfo
        ]);
    }


    // ===== ENDPOINTS PROFIL =====

    #[Route('/profile', name: 'profile_show', methods: ['GET'])]
    public function showProfile(): JsonResponse
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
                'photo' => $user->getPhoto(),
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

    #[Route('/profile', name: 'profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
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
            $devise = $this->entityManager->getRepository(\App\Entity\Devise::class)->find($data['devise_id']);
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
                ],
                'dateCreation' => $user->getDateCreation()?->format('Y-m-d H:i:s')
            ]
        ]);
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

        $devise = $this->entityManager->getRepository(\App\Entity\Devise::class)->find($data['devise_id']);
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

    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    public function getStatistiques(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvementRepo = $this->entityManager->getRepository(\App\Entity\Mouvement::class);
        $compteRepo = $this->entityManager->getRepository(\App\Entity\Compte::class);
        $depensePrevueRepo = $this->entityManager->getRepository(\App\Entity\DepensePrevue::class);
        $categorieRepo = $this->entityManager->getRepository(\App\Entity\Categorie::class);
        $contactRepo = $this->entityManager->getRepository(\App\Entity\Contact::class);

        // Statistiques générales
        $totalMouvements = count($mouvementRepo->findBy(['user' => $user]));
        $totalComptes = count($compteRepo->findBy(['user' => $user]));
        $totalDepensesPrevues = count($depensePrevueRepo->findBy(['user' => $user]));
        $totalCategories = count($categorieRepo->findBy(['user' => $user]));
        $totalContacts = count($contactRepo->findBy(['user' => $user]));

        // Solde total
        $soldeTotal = $mouvementRepo->getSoldeTotal($user);

        // Dernière activité
        $dernierMouvement = $mouvementRepo->findOneBy(['user' => $user], ['date' => 'DESC']);

        return new JsonResponse([
            'statistiques' => [
                'totalMouvements' => $totalMouvements,
                'totalComptes' => $totalComptes,
                'totalDepensesPrevues' => $totalDepensesPrevues,
                'totalCategories' => $totalCategories,
                'totalContacts' => $totalContacts,
                'soldeTotal' => number_format($soldeTotal, 2, '.', ''),
                'soldeTotalFormatted' => $this->userDeviseService->formatAmount($user, $soldeTotal)
            ],
            'derniereActivite' => $dernierMouvement ? [
                'id' => $dernierMouvement->getId(),
                'type' => $dernierMouvement->getType(),
                'typeLabel' => $dernierMouvement->getTypeLabel(),
                'montant' => $dernierMouvement->getMontantEffectif(),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $dernierMouvement->getMontantEffectif()),
                'date' => $dernierMouvement->getDate()->format('Y-m-d H:i:s'),
                'description' => $dernierMouvement->getDescription()
            ] : null,
            'compte' => [
                'dateCreation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                'joursActif' => $user->getDateCreation()->diff(new \DateTime())->days
            ]
        ]);
    }

    #[Route('/preferences', name: 'preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'preferences' => [
                'devise' => [
                    'id' => $user->getDevise()->getId(),
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ],
                'notifications' => [
                    'dettesEnRetard' => true,
                    'nouveauxMouvements' => true,
                    'rapportsMensuels' => false
                ],
                'affichage' => [
                    'formatDate' => 'DD/MM/YYYY',
                    'formatMontant' => 'avecSeparateurs',
                    'theme' => 'clair'
                ]
            ]
        ]);
    }

    #[Route('/preferences', name: 'update_preferences', methods: ['PUT'])]
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour la devise si fournie
        if (isset($data['devise_id'])) {
            $devise = $this->entityManager->getRepository(\App\Entity\Devise::class)->find($data['devise_id']);
            if ($devise) {
                $this->userDeviseService->changeUserDevise($user, $devise);
            }
        }

        return new JsonResponse([
            'message' => 'Préférences mises à jour avec succès',
            'preferences' => [
                'devise' => [
                    'id' => $user->getDevise()->getId(),
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ]
            ]
        ]);
    }

    #[Route('/export-data', name: 'export_data', methods: ['GET'])]
    public function exportData(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer toutes les données de l'utilisateur
        $mouvements = $this->entityManager->getRepository(\App\Entity\Mouvement::class)->findBy(['user' => $user]);
        $comptes = $this->entityManager->getRepository(\App\Entity\Compte::class)->findBy(['user' => $user]);
        $depensesPrevues = $this->entityManager->getRepository(\App\Entity\DepensePrevue::class)->findBy(['user' => $user]);
        $categories = $this->entityManager->getRepository(\App\Entity\Categorie::class)->findBy(['user' => $user]);
        $contacts = $this->entityManager->getRepository(\App\Entity\Contact::class)->findBy(['user' => $user]);

        $exportData = [
            'utilisateur' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenoms' => $user->getPrenoms(),
                'dateCreation' => $user->getDateCreation()->format('Y-m-d H:i:s'),
                'devise' => [
                    'code' => $user->getDevise()->getCode(),
                    'nom' => $user->getDevise()->getNom()
                ]
            ],
            'mouvements' => array_map(function($mouvement) use ($user) {
                return [
                    'id' => $mouvement->getId(),
                    'type' => $mouvement->getType(),
                    'montantTotal' => $mouvement->getMontantTotal(),
                    'montantEffectif' => $mouvement->getMontantEffectif(),
                    'statut' => $mouvement->getStatut(),
                    'date' => $mouvement->getDate()->format('Y-m-d H:i:s'),
                    'description' => $mouvement->getDescription(),
                    'categorie' => $mouvement->getCategorie()->getNom(),
                    'depensePrevue' => $mouvement->getDepensePrevue()?->getNom(),
                    'contact' => $mouvement->getContact()?->getNom()
                ];
            }, $mouvements),
            'comptes' => array_map(function($compte) {
                return [
                    'id' => $compte->getId(),
                    'nom' => $compte->getNom(),
                    'type' => $compte->getType(),
                    'soldeActuel' => $compte->getSoldeActuel(),
                    'soldeInitial' => $compte->getSoldeInitial(),
                    'dateCreation' => $compte->getDateCreation()->format('Y-m-d H:i:s')
                ];
            }, $comptes),
            'depensesPrevues' => array_map(function($depensePrevue) {
                return [
                    'id' => $depensePrevue->getId(),
                    'nom' => $depensePrevue->getNom(),
                    'description' => $depensePrevue->getDescription(),
                    'montantPrevu' => $depensePrevue->getBudgetPrevu(),
                    'typeBudget' => $depensePrevue->getTypeBudget(),
                    'statut' => $depensePrevue->getStatut(),
                    'dateCreation' => $depensePrevue->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }, $depensesPrevues),
            'categories' => array_map(function($categorie) {
                return [
                    'id' => $categorie->getId(),
                    'nom' => $categorie->getNom(),
                    'type' => $categorie->getType(),
                    'dateCreation' => $categorie->getDateCreation()->format('Y-m-d H:i:s')
                ];
            }, $categories),
            'contacts' => array_map(function($contact) {
                return [
                    'id' => $contact->getId(),
                    'nom' => $contact->getNom(),
                    'telephone' => $contact->getTelephone(),
                    'email' => $contact->getEmail(),
                    'source' => $contact->getSource(),
                    'dateCreation' => $contact->getDateCreation()->format('Y-m-d H:i:s')
                ];
            }, $contacts),
            'export' => [
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'totalMouvements' => count($mouvements),
                'totalComptes' => count($comptes),
                'totalDepensesPrevues' => count($depensesPrevues),
                'totalCategories' => count($categories),
                'totalContacts' => count($contacts)
            ]
        ];

        return new JsonResponse([
            'message' => 'Données exportées avec succès',
            'data' => $exportData
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

    #[Route('/delete-account', name: 'delete_account', methods: ['DELETE'])]
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $password = $data['password'] ?? null;

        if (!$password) {
            return new JsonResponse([
                'error' => 'Mot de passe requis',
                'message' => 'Veuillez confirmer votre mot de passe pour supprimer votre compte'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'error' => 'Mot de passe incorrect',
                'message' => 'Le mot de passe fourni est incorrect'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Supprimer la photo de profil si elle existe
            if ($user->getPhoto()) {
                $this->photoUploadService->delete($user->getPhoto());
            }

            // Supprimer tous les mouvements de l'utilisateur
            $mouvements = $this->entityManager->getRepository(\App\Entity\Mouvement::class)
                ->findBy(['user' => $user]);
            
            foreach ($mouvements as $mouvement) {
                $this->entityManager->remove($mouvement);
            }

            // Supprimer tous les comptes de l'utilisateur
            $comptes = $this->entityManager->getRepository(\App\Entity\Compte::class)
                ->findBy(['user' => $user]);
            
            foreach ($comptes as $compte) {
                $this->entityManager->remove($compte);
            }

            // Supprimer tous les contacts de l'utilisateur
            $contacts = $this->entityManager->getRepository(\App\Entity\Contact::class)
                ->findBy(['user' => $user]);
            
            foreach ($contacts as $contact) {
                $this->entityManager->remove($contact);
            }

            // Supprimer toutes les dépenses prévues de l'utilisateur
            $depensesPrevues = $this->entityManager->getRepository(\App\Entity\DepensePrevue::class)
                ->findBy(['user' => $user]);
            
            foreach ($depensesPrevues as $depensePrevue) {
                $this->entityManager->remove($depensePrevue);
            }

            // Supprimer l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Compte supprimé avec succès',
                'success' => true
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la suppression',
                'message' => 'Une erreur est survenue lors de la suppression de votre compte'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
