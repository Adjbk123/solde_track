<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Entity\Paiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/paiements', name: 'api_paiements_')]
class PaiementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    #[Route('/mouvement/{mouvementId}', name: 'list_by_mouvement', methods: ['GET'])]
    public function listByMouvement(int $mouvementId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($mouvementId);
        if (!$mouvement || $mouvement->getUser() !== $user) {
            return new JsonResponse(['error' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $paiements = $this->entityManager->getRepository(Paiement::class)
            ->findByMouvement($mouvement);

        $data = [];
        foreach ($paiements as $paiement) {
            $data[] = [
                'id' => $paiement->getId(),
                'montant' => $paiement->getMontant(),
                'date' => $paiement->getDate()->format('Y-m-d H:i:s'),
                'commentaire' => $paiement->getCommentaire(),
                'statut' => $paiement->getStatut(),
                'statutLabel' => $paiement->getStatutLabel()
            ];
        }

        return new JsonResponse([
            'paiements' => $data,
            'totalPaiements' => $this->entityManager->getRepository(Paiement::class)
                ->getTotalPaiements($mouvement)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['mouvement_id']) || !isset($data['montant'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'ID du mouvement et montant sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($data['mouvement_id']);
        if (!$mouvement || $mouvement->getUser() !== $user) {
            return new JsonResponse(['error' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $paiement = new Paiement();
        $paiement->setMouvement($mouvement);
        $paiement->setMontant($data['montant']);
        $paiement->setCommentaire($data['commentaire'] ?? null);
        $paiement->setStatut($data['statut'] ?? Paiement::STATUT_PAYE);

        if (isset($data['date'])) {
            $paiement->setDate(new \DateTime($data['date']));
        }

        $errors = $this->validator->validate($paiement);
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

        $this->entityManager->persist($paiement);
        $this->entityManager->flush();

        // Mettre à jour le montant effectif du mouvement
        $totalPaiements = $this->entityManager->getRepository(Paiement::class)
            ->getTotalPaiements($mouvement);
        $mouvement->setMontantEffectif($totalPaiements);
        $mouvement->updateStatut();
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Paiement créé avec succès',
            'paiement' => [
                'id' => $paiement->getId(),
                'montant' => $paiement->getMontant(),
                'date' => $paiement->getDate()->format('Y-m-d H:i:s'),
                'commentaire' => $paiement->getCommentaire(),
                'statut' => $paiement->getStatut(),
                'statutLabel' => $paiement->getStatutLabel()
            ],
            'mouvement' => [
                'montantEffectif' => $mouvement->getMontantEffectif(),
                'montantRestant' => $mouvement->getMontantRestant(),
                'statut' => $mouvement->getStatut(),
                'statutLabel' => $mouvement->getStatutLabel()
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

        $paiement = $this->entityManager->getRepository(Paiement::class)->find($id);
        if (!$paiement || $paiement->getMouvement()->getUser() !== $user) {
            return new JsonResponse(['error' => 'Paiement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['montant'])) {
            $paiement->setMontant($data['montant']);
        }
        if (isset($data['commentaire'])) {
            $paiement->setCommentaire($data['commentaire']);
        }
        if (isset($data['statut'])) {
            $paiement->setStatut($data['statut']);
        }
        if (isset($data['date'])) {
            $paiement->setDate(new \DateTime($data['date']));
        }

        $errors = $this->validator->validate($paiement);
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

        // Mettre à jour le montant effectif du mouvement
        $mouvement = $paiement->getMouvement();
        $totalPaiements = $this->entityManager->getRepository(Paiement::class)
            ->getTotalPaiements($mouvement);
        $mouvement->setMontantEffectif($totalPaiements);
        $mouvement->updateStatut();
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Paiement mis à jour avec succès',
            'paiement' => [
                'id' => $paiement->getId(),
                'montant' => $paiement->getMontant(),
                'date' => $paiement->getDate()->format('Y-m-d H:i:s'),
                'commentaire' => $paiement->getCommentaire(),
                'statut' => $paiement->getStatut(),
                'statutLabel' => $paiement->getStatutLabel()
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

        $paiement = $this->entityManager->getRepository(Paiement::class)->find($id);
        if (!$paiement || $paiement->getMouvement()->getUser() !== $user) {
            return new JsonResponse(['error' => 'Paiement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $mouvement = $paiement->getMouvement();
        $this->entityManager->remove($paiement);
        $this->entityManager->flush();

        // Mettre à jour le montant effectif du mouvement
        $totalPaiements = $this->entityManager->getRepository(Paiement::class)
            ->getTotalPaiements($mouvement);
        $mouvement->setMontantEffectif($totalPaiements);
        $mouvement->updateStatut();
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Paiement supprimé avec succès'
        ]);
    }
}
