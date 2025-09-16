<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\User;
use App\Service\DefaultComptesService;
use App\Service\UserDeviseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comptes', name: 'api_comptes_')]
class CompteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private DefaultComptesService $defaultComptesService,
        private UserDeviseService $userDeviseService
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $comptes = $this->entityManager->getRepository(Compte::class)->findActifsByUser($user);
        
        $data = [];
        foreach ($comptes as $compte) {
            $data[] = [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'description' => $compte->getDescription(),
                'type' => $compte->getType(),
                'typeLabel' => $compte->getTypeLabel(),
                'soldeInitial' => $compte->getSoldeInitial(),
                'soldeActuel' => $compte->getSoldeActuel(),
                'soldeActuelFormatted' => $this->userDeviseService->formatAmount($user, (float) $compte->getSoldeActuel()),
                'devise' => [
                    'id' => $compte->getDevise()->getId(),
                    'code' => $compte->getDevise()->getCode(),
                    'nom' => $compte->getDevise()->getNom()
                ],
                'numero' => $compte->getNumero(),
                'institution' => $compte->getInstitution(),
                'dateCreation' => $compte->getDateCreation()->format('Y-m-d H:i:s'),
                'dateModification' => $compte->getDateModification()?->format('Y-m-d H:i:s'),
                'actif' => $compte->isActif()
            ];
        }

        return new JsonResponse(['comptes' => $data]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || !isset($data['type'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Nom et type sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $compte = new Compte();
        $compte->setUser($user);
        $compte->setNom($data['nom']);
        $compte->setType($data['type']);
        $compte->setDescription($data['description'] ?? null);
        $compte->setDevise($user->getDevise());
        $compte->setSoldeInitial($data['solde_initial'] ?? '0.00');
        $compte->setSoldeActuel($data['solde_initial'] ?? '0.00');
        $compte->setNumero($data['numero'] ?? null);
        $compte->setInstitution($data['institution'] ?? null);
        $compte->setActif(true);

        // Validation
        $errors = $this->validator->validate($compte);
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

        $this->entityManager->persist($compte);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Compte créé avec succès',
            'compte' => [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'type' => $compte->getType(),
                'typeLabel' => $compte->getTypeLabel(),
                'soldeActuel' => $compte->getSoldeActuel(),
                'soldeActuelFormatted' => $this->userDeviseService->formatAmount($user, (float) $compte->getSoldeActuel())
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $compteId = (int) $id;
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        
        if (!$compte || $compte->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'compte' => [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'description' => $compte->getDescription(),
                'type' => $compte->getType(),
                'typeLabel' => $compte->getTypeLabel(),
                'soldeInitial' => $compte->getSoldeInitial(),
                'soldeActuel' => $compte->getSoldeActuel(),
                'soldeActuelFormatted' => $this->userDeviseService->formatAmount($user, (float) $compte->getSoldeActuel()),
                'devise' => [
                    'id' => $compte->getDevise()->getId(),
                    'code' => $compte->getDevise()->getCode(),
                    'nom' => $compte->getDevise()->getNom()
                ],
                'numero' => $compte->getNumero(),
                'institution' => $compte->getInstitution(),
                'dateCreation' => $compte->getDateCreation()->format('Y-m-d H:i:s'),
                'dateModification' => $compte->getDateModification()?->format('Y-m-d H:i:s'),
                'actif' => $compte->isActif(),
                'nombreMouvements' => $compte->getMouvements()->count()
            ]
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $compteId = (int) $id;
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        
        if (!$compte || $compte->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $compte->setNom($data['nom']);
        }
        if (isset($data['description'])) {
            $compte->setDescription($data['description']);
        }
        if (isset($data['type'])) {
            $compte->setType($data['type']);
        }
        if (isset($data['numero'])) {
            $compte->setNumero($data['numero']);
        }
        if (isset($data['institution'])) {
            $compte->setInstitution($data['institution']);
        }

        // Validation
        $errors = $this->validator->validate($compte);
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
            'message' => 'Compte modifié avec succès',
            'compte' => [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'type' => $compte->getType(),
                'typeLabel' => $compte->getTypeLabel()
            ]
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $compteId = (int) $id;
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        
        if (!$compte || $compte->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier s'il y a des mouvements liés
        if ($compte->getMouvements()->count() > 0) {
            return new JsonResponse([
                'error' => 'Impossible de supprimer',
                'message' => 'Ce compte contient des mouvements. Désactivez-le plutôt.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($compte);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Compte supprimé avec succès']);
    }

    #[Route('/{id}/desactiver', name: 'desactiver', methods: ['POST'])]
    public function desactiver(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $compteId = (int) $id;
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        
        if (!$compte || $compte->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->defaultComptesService->desactiverCompte($compte);

        return new JsonResponse(['message' => 'Compte désactivé avec succès']);
    }

    #[Route('/{id}/reactiver', name: 'reactiver', methods: ['POST'])]
    public function reactiver(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $compteId = (int) $id;
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        
        if (!$compte || $compte->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->defaultComptesService->reactiverCompte($compte);

        return new JsonResponse(['message' => 'Compte réactivé avec succès']);
    }

    #[Route('/types', name: 'types', methods: ['GET'])]
    public function getTypes(): JsonResponse
    {
        return new JsonResponse(['types' => Compte::getTypes()]);
    }

    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    public function getStatistiques(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $compteRepo = $this->entityManager->getRepository(Compte::class);
        
        $statistiques = $compteRepo->getStatistiquesParType($user);
        $soldeTotal = $compteRepo->getSoldeTotalByUser($user);
        $comptesNegatifs = $compteRepo->findComptesAvecSoldeNegatif($user);

        return new JsonResponse([
            'statistiques' => $statistiques,
            'soldeTotal' => $soldeTotal,
            'soldeTotalFormatted' => $this->userDeviseService->formatAmount($user, $soldeTotal),
            'comptesNegatifs' => count($comptesNegatifs),
            'devise' => [
                'code' => $this->userDeviseService->getUserDeviseCode($user),
                'nom' => $this->userDeviseService->getUserDeviseName($user)
            ]
        ]);
    }
}
