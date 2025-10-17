<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Entity\User;
use App\Service\MouvementUnifieService;
use App\Service\ResponseService;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mouvements-unifies', name: 'mouvements_unifies_')]
class MouvementUnifieController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MouvementUnifieService $mouvementService,
        private ValidationService $validationService
    ) {}

    /**
     * Créer un mouvement (sortie, entrée, don)
     */
    #[Route('/{type}', name: 'creer', methods: ['POST'])]
    public function creerMouvement(string $type, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ResponseService::unauthorized();
        }

        try {
            // Validation du type
            if (!$this->validationService->validerTypeMouvement($type)) {
                return ResponseService::error('Type de mouvement invalide');
            }

            $donnees = json_decode($request->getContent(), true);
            $donnees = $this->validationService->nettoyerDonnees($donnees);

            // Validation des données
            $erreurs = $this->validationService->validerDonneesMouvement($donnees);
            if (!empty($erreurs)) {
                return ResponseService::validationError($erreurs);
            }

            $mouvement = $this->mouvementService->creerMouvement($user, $type, $donnees);

            return ResponseService::created([
                'mouvement' => $this->serialiserMouvement($mouvement)
            ], 'Mouvement créé avec succès');

        } catch (\InvalidArgumentException $e) {
            return ResponseService::error($e->getMessage());

        } catch (\Exception $e) {
            return ResponseService::serverError($e->getMessage());
        }
    }

    /**
     * Lister les mouvements avec filtres
     */
    #[Route('', name: 'lister', methods: ['GET'])]
    public function listerMouvements(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $filtres = $this->extraireFiltres($request);
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);

            $qb = $this->entityManager->getRepository(Mouvement::class)
                ->createQueryBuilder('m')
                ->where('m.user = :user')
                ->setParameter('user', $user)
                ->orderBy('m.date', 'DESC');

            // Appliquer les filtres
            if (isset($filtres['type'])) {
                $qb->andWhere('m.type = :type')->setParameter('type', $filtres['type']);
            }
            if (isset($filtres['depense_prevue_id'])) {
                $qb->andWhere('m.depensePrevue = :depensePrevue')->setParameter('depensePrevue', $filtres['depense_prevue_id']);
            }
            if (isset($filtres['categorie_id'])) {
                $qb->andWhere('m.categorie = :categorie')->setParameter('categorie', $filtres['categorie_id']);
            }
            if (isset($filtres['compte_id'])) {
                $qb->andWhere('m.compte = :compte')->setParameter('compte', $filtres['compte_id']);
            }
            if (isset($filtres['contact_id'])) {
                $qb->andWhere('m.contact = :contact')->setParameter('contact', $filtres['contact_id']);
            }
            if (isset($filtres['date_debut'])) {
                $qb->andWhere('m.date >= :dateDebut')->setParameter('dateDebut', $filtres['date_debut']);
            }
            if (isset($filtres['date_fin'])) {
                $qb->andWhere('m.date <= :dateFin')->setParameter('dateFin', $filtres['date_fin']);
            }

            // Pagination
            $total = count($qb->getQuery()->getResult());
            $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

            $mouvements = $qb->getQuery()->getResult();

            return new JsonResponse([
                'mouvements' => array_map([$this, 'serialiserMouvement'], $mouvements),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les détails d'un mouvement
     */
    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function detailsMouvement(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($id);
            if (!$mouvement || $mouvement->getUser() !== $user) {
                return new JsonResponse(['erreur' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'mouvement' => $this->serialiserMouvement($mouvement)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Modifier un mouvement
     */
    #[Route('/{id}', name: 'modifier', methods: ['PUT'])]
    public function modifierMouvement(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($id);
            if (!$mouvement || $mouvement->getUser() !== $user) {
                return new JsonResponse(['erreur' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $donnees = json_decode($request->getContent(), true);
            $mouvement = $this->mouvementService->mettreAJourMouvement($mouvement, $donnees);

            return new JsonResponse([
                'message' => 'Mouvement modifié avec succès',
                'mouvement' => $this->serialiserMouvement($mouvement)
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'erreur' => 'Données invalides',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer un mouvement
     */
    #[Route('/{id}', name: 'supprimer', methods: ['DELETE'])]
    public function supprimerMouvement(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($id);
            if (!$mouvement || $mouvement->getUser() !== $user) {
                return new JsonResponse(['erreur' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $this->mouvementService->supprimerMouvement($mouvement);

            return new JsonResponse([
                'message' => 'Mouvement supprimé avec succès'
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'erreur' => 'Impossible de supprimer',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les mouvements récents
     */
    #[Route('/recents', name: 'recents', methods: ['GET'])]
    public function mouvementsRecents(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $limit = (int) $request->query->get('limit', 10);
            $type = $request->query->get('type');

            $qb = $this->entityManager->getRepository(Mouvement::class)
                ->createQueryBuilder('m')
                ->where('m.user = :user')
                ->setParameter('user', $user)
                ->orderBy('m.date', 'DESC')
                ->setMaxResults($limit);

            if ($type) {
                $qb->andWhere('m.type = :type')->setParameter('type', $type);
            }

            $mouvements = $qb->getQuery()->getResult();

            return new JsonResponse([
                'mouvements' => array_map([$this, 'serialiserMouvement'], $mouvements)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Méthodes privées

    private function extraireFiltres(Request $request): array
    {
        $filtres = [];

        if ($request->query->has('type')) {
            $filtres['type'] = $request->query->get('type');
        }
        if ($request->query->has('depense_prevue_id')) {
            $filtres['depense_prevue_id'] = (int) $request->query->get('depense_prevue_id');
        }
        if ($request->query->has('categorie_id')) {
            $filtres['categorie_id'] = (int) $request->query->get('categorie_id');
        }
        if ($request->query->has('compte_id')) {
            $filtres['compte_id'] = (int) $request->query->get('compte_id');
        }
        if ($request->query->has('contact_id')) {
            $filtres['contact_id'] = (int) $request->query->get('contact_id');
        }
        if ($request->query->has('date_debut')) {
            $filtres['date_debut'] = new \DateTime($request->query->get('date_debut'));
        }
        if ($request->query->has('date_fin')) {
            $filtres['date_fin'] = new \DateTime($request->query->get('date_fin'));
        }

        return $filtres;
    }

    private function serialiserMouvement(Mouvement $mouvement): array
    {
        $data = [
            'id' => $mouvement->getId(),
            'type' => $mouvement->getType(),
            'typeLabel' => $mouvement->getTypeLabel(),
            'montantTotal' => $mouvement->getMontantTotal(),
            'montantTotalFormatted' => number_format((float) $mouvement->getMontantTotal(), 0, ',', ' ') . ' F',
            'montantEffectif' => $mouvement->getMontantEffectif(),
            'montantEffectifFormatted' => number_format((float) $mouvement->getMontantEffectif(), 0, ',', ' ') . ' F',
            'statut' => $mouvement->getStatut(),
            'statutLabel' => $mouvement->getStatutLabel(),
            'date' => $mouvement->getDate()->format('Y-m-d'),
            'description' => $mouvement->getDescription(),
            'categorie' => $mouvement->getCategorie() ? [
                'id' => $mouvement->getCategorie()->getId(),
                'nom' => $mouvement->getCategorie()->getNom(),
                'type' => $mouvement->getCategorie()->getType()
            ] : null,
            'compte' => $mouvement->getCompte() ? [
                'id' => $mouvement->getCompte()->getId(),
                'nom' => $mouvement->getCompte()->getNom(),
                'type' => $mouvement->getCompte()->getType()
            ] : null,
            'contact' => $mouvement->getContact() ? [
                'id' => $mouvement->getContact()->getId(),
                'nom' => $mouvement->getContact()->getNom(),
                'telephone' => $mouvement->getContact()->getTelephone()
            ] : null,
            'depensePrevue' => $mouvement->getDepensePrevue() ? [
                'id' => $mouvement->getDepensePrevue()->getId(),
                'nom' => $mouvement->getDepensePrevue()->getNom()
            ] : null
        ];

        // Ajouter les champs spécifiques selon le type
        if ($mouvement instanceof \App\Entity\Depense) {
            $data['lieu'] = $mouvement->getLieu();
            $data['methodePaiement'] = $mouvement->getMethodePaiement();
            $data['methodePaiementLabel'] = $mouvement->getMethodePaiementLabel();
            $data['recu'] = $mouvement->getRecu();
        } elseif ($mouvement instanceof \App\Entity\Entree) {
            $data['source'] = $mouvement->getSource();
            $data['methode'] = $mouvement->getMethode();
            $data['methodeLabel'] = $mouvement->getMethodeLabel();
        } elseif ($mouvement instanceof \App\Entity\Don) {
            $data['occasion'] = $mouvement->getOccasion();
        }

        return $data;
    }
}
