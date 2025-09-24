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
                ],
                'dateCreation' => $user->getDateCreation()?->format('Y-m-d H:i:s')
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

        // Hasher et définir le nouveau mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Mot de passe modifié avec succès'
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
        $projetRepo = $this->entityManager->getRepository(\App\Entity\Projet::class);
        $categorieRepo = $this->entityManager->getRepository(\App\Entity\Categorie::class);
        $contactRepo = $this->entityManager->getRepository(\App\Entity\Contact::class);

        // Statistiques générales
        $totalMouvements = count($mouvementRepo->findBy(['user' => $user]));
        $totalComptes = count($compteRepo->findBy(['user' => $user]));
        $totalProjets = count($projetRepo->findBy(['user' => $user]));
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
                'totalProjets' => $totalProjets,
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
            $devise = $this->entityManager->getRepository(Devise::class)->find($data['devise_id']);
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
        $projets = $this->entityManager->getRepository(\App\Entity\Projet::class)->findBy(['user' => $user]);
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
                    'projet' => $mouvement->getProjet()?->getNom(),
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
            'projets' => array_map(function($projet) {
                return [
                    'id' => $projet->getId(),
                    'nom' => $projet->getNom(),
                    'description' => $projet->getDescription(),
                    'budgetPrevu' => $projet->getBudgetPrevu(),
                    'dateCreation' => $projet->getDateCreation()->format('Y-m-d H:i:s')
                ];
            }, $projets),
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
                'totalProjets' => count($projets),
                'totalCategories' => count($categories),
                'totalContacts' => count($contacts)
            ]
        ];

        return new JsonResponse([
            'message' => 'Données exportées avec succès',
            'data' => $exportData
        ]);
    }
}
