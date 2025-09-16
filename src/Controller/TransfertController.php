<?php

namespace App\Controller;

use App\Entity\Transfert;
use App\Entity\User;
use App\Entity\Compte;
use App\Service\TransfertService;
use App\Service\UserDeviseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transferts', name: 'api_transferts_')]
class TransfertController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TransfertService $transfertService,
        private UserDeviseService $userDeviseService
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $compteId = $request->query->get('compte_id');

        $transferts = $this->transfertService->getTransfertsByUser($user);

        // Filtrer par compte si spécifié
        if ($compteId) {
            $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
            if ($compte && $compte->getUser() === $user) {
                $transferts = array_filter($transferts, function($transfert) use ($compte) {
                    return $transfert->getCompteSource() === $compte || $transfert->getCompteDestination() === $compte;
                });
            }
        }

        // Pagination
        $total = count($transferts);
        $offset = ($page - 1) * $limit;
        $transferts = array_slice($transferts, $offset, $limit);

        $data = [];
        foreach ($transferts as $transfert) {
            $data[] = [
                'id' => $transfert->getId(),
                'montant' => $transfert->getMontant(),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $transfert->getMontant()),
                'devise' => [
                    'id' => $transfert->getDevise()->getId(),
                    'code' => $transfert->getDevise()->getCode(),
                    'nom' => $transfert->getDevise()->getNom()
                ],
                'compteSource' => [
                    'id' => $transfert->getCompteSource()->getId(),
                    'nom' => $transfert->getCompteSource()->getNom(),
                    'type' => $transfert->getCompteSource()->getType()
                ],
                'compteDestination' => [
                    'id' => $transfert->getCompteDestination()->getId(),
                    'nom' => $transfert->getCompteDestination()->getNom(),
                    'type' => $transfert->getCompteDestination()->getType()
                ],
                'date' => $transfert->getDate()->format('Y-m-d H:i:s'),
                'note' => $transfert->getNote(),
                'description' => $transfert->getDescription()
            ];
        }

        return new JsonResponse([
            'transferts' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['compte_source_id']) || !isset($data['compte_destination_id']) || !isset($data['montant'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'compte_source_id, compte_destination_id et montant sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $compteSource = $this->entityManager->getRepository(Compte::class)->find($data['compte_source_id']);
        $compteDestination = $this->entityManager->getRepository(Compte::class)->find($data['compte_destination_id']);

        if (!$compteSource || $compteSource->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte source non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$compteDestination || $compteDestination->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte destination non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $transfert = $this->transfertService->creerTransfert(
                $user,
                $compteSource,
                $compteDestination,
                $data['montant'],
                $data['note'] ?? null
            );

            return new JsonResponse([
                'message' => 'Transfert effectué avec succès',
                'transfert' => [
                    'id' => $transfert->getId(),
                    'montant' => $transfert->getMontant(),
                    'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $transfert->getMontant()),
                    'compteSource' => [
                        'id' => $transfert->getCompteSource()->getId(),
                        'nom' => $transfert->getCompteSource()->getNom(),
                        'nouveauSolde' => $transfert->getCompteSource()->getSoldeActuel()
                    ],
                    'compteDestination' => [
                        'id' => $transfert->getCompteDestination()->getId(),
                        'nom' => $transfert->getCompteDestination()->getNom(),
                        'nouveauSolde' => $transfert->getCompteDestination()->getSoldeActuel()
                    ],
                    'description' => $transfert->getDescription()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors du transfert',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    
    #[Route('/simuler', name: 'simuler', methods: ['POST'])]
    public function simuler(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['compte_source_id']) || !isset($data['compte_destination_id']) || !isset($data['montant'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'compte_source_id, compte_destination_id et montant sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $compteSource = $this->entityManager->getRepository(Compte::class)->find($data['compte_source_id']);
        $compteDestination = $this->entityManager->getRepository(Compte::class)->find($data['compte_destination_id']);

        if (!$compteSource || $compteSource->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte source non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$compteDestination || $compteDestination->getUser() !== $user) {
            return new JsonResponse(['error' => 'Compte destination non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $simulation = $this->transfertService->simulerTransfert($compteSource, $compteDestination, $data['montant']);

        return new JsonResponse($simulation);
    }

    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    public function getStatistiques(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $statistiques = $this->transfertService->getStatistiquesTransferts($user);

        return new JsonResponse($statistiques);
    }

    #[Route('/recents', name: 'recents', methods: ['GET'])]
    public function getRecents(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $limit = (int) $request->query->get('limit', 10);
        $transferts = $this->transfertService->getTransfertsRecents($user, $limit);

        $data = [];
        foreach ($transferts as $transfert) {
            $data[] = [
                'id' => $transfert->getId(),
                'montant' => $transfert->getMontant(),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $transfert->getMontant()),
                'compteSource' => $transfert->getCompteSource()->getNom(),
                'compteDestination' => $transfert->getCompteDestination()->getNom(),
                'date' => $transfert->getDate()->format('Y-m-d H:i:s'),
                'description' => $transfert->getDescription()
            ];
        }

        return new JsonResponse(['transferts' => $data]);
    }

    
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $transfertId = (int) $id;
        $transfert = $this->entityManager->getRepository(Transfert::class)->find($transfertId);
        
        if (!$transfert || $transfert->getUser() !== $user) {
            return new JsonResponse(['error' => 'Transfert non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'transfert' => [
                'id' => $transfert->getId(),
                'montant' => $transfert->getMontant(),
                'montantFormatted' => $this->userDeviseService->formatAmount($user, (float) $transfert->getMontant()),
                'devise' => [
                    'id' => $transfert->getDevise()->getId(),
                    'code' => $transfert->getDevise()->getCode(),
                    'nom' => $transfert->getDevise()->getNom()
                ],
                'compteSource' => [
                    'id' => $transfert->getCompteSource()->getId(),
                    'nom' => $transfert->getCompteSource()->getNom(),
                    'type' => $transfert->getCompteSource()->getType(),
                    'typeLabel' => $transfert->getCompteSource()->getTypeLabel()
                ],
                'compteDestination' => [
                    'id' => $transfert->getCompteDestination()->getId(),
                    'nom' => $transfert->getCompteDestination()->getNom(),
                    'type' => $transfert->getCompteDestination()->getType(),
                    'typeLabel' => $transfert->getCompteDestination()->getTypeLabel()
                ],
                'date' => $transfert->getDate()->format('Y-m-d H:i:s'),
                'dateCreation' => $transfert->getDateCreation()->format('Y-m-d H:i:s'),
                'note' => $transfert->getNote(),
                'description' => $transfert->getDescription()
            ]
        ]);
    }

    #[Route('/{id}/annuler', name: 'annuler', methods: ['POST'])]
    public function annuler(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $transfertId = (int) $id;
        $transfert = $this->entityManager->getRepository(Transfert::class)->find($transfertId);
        
        if (!$transfert || $transfert->getUser() !== $user) {
            return new JsonResponse(['error' => 'Transfert non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->transfertService->annulerTransfert($transfert);

            return new JsonResponse([
                'message' => 'Transfert annulé avec succès',
                'transfert' => [
                    'id' => $transfert->getId(),
                    'compteSource' => [
                        'id' => $transfert->getCompteSource()->getId(),
                        'nom' => $transfert->getCompteSource()->getNom(),
                        'nouveauSolde' => $transfert->getCompteSource()->getSoldeActuel()
                    ],
                    'compteDestination' => [
                        'id' => $transfert->getCompteDestination()->getId(),
                        'nom' => $transfert->getCompteDestination()->getNom(),
                        'nouveauSolde' => $transfert->getCompteDestination()->getSoldeActuel()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'annulation',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

}
