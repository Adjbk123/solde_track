<?php

namespace App\Controller;

use App\Entity\Dette;
use App\Entity\PaiementDette;
use App\Entity\User;
use App\Service\DetteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// SerializerInterface supprimé car non nécessaire

#[Route('/api/dettes', name: 'dettes_')]
class DetteController extends AbstractController
{
    public function __construct(
        private DetteService $detteService
    ) {}

    /**
     * Créer une nouvelle dette
     */
    #[Route('', name: 'creer', methods: ['POST'])]
    public function creerDette(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $donnees = json_decode($request->getContent(), true);
            $dette = $this->detteService->creerDette($user, $donnees);

            return new JsonResponse([
                'message' => 'Dette créée avec succès',
                'dette' => $this->serialiserDette($dette)
            ], Response::HTTP_CREATED);

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
     * Lister les dettes avec filtres optionnels
     */
    #[Route('', name: 'lister', methods: ['GET'])]
    public function listerDettes(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $filtres = $this->extraireFiltres($request);
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 20);

            $dettes = $this->detteService->recupererDettes($user, $filtres);
            $total = count($dettes);

            // Pagination simple
            $dettesPaginees = array_slice($dettes, ($page - 1) * $limit, $limit);

            return new JsonResponse([
                'dettes' => array_map([$this, 'serialiserDette'], $dettesPaginees),
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
     * Récupérer les détails d'une dette
     */
    #[Route('/{id}', name: 'details', methods: ['GET'])]
    public function detailsDette(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $dette = $this->detteService->recupererDette($user, $id);
            if (!$dette) {
                return new JsonResponse(['erreur' => 'Dette non trouvée'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'dette' => $this->serialiserDetteComplete($dette)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Modifier une dette
     */
    #[Route('/{id}', name: 'modifier', methods: ['PUT'])]
    public function modifierDette(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $dette = $this->detteService->recupererDette($user, $id);
            if (!$dette) {
                return new JsonResponse(['erreur' => 'Dette non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $donnees = json_decode($request->getContent(), true);
            $dette = $this->detteService->mettreAJourDette($dette, $donnees);

            return new JsonResponse([
                'message' => 'Dette modifiée avec succès',
                'dette' => $this->serialiserDette($dette)
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
     * Supprimer une dette
     */
    #[Route('/{id}', name: 'supprimer', methods: ['DELETE'])]
    public function supprimerDette(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $dette = $this->detteService->recupererDette($user, $id);
            if (!$dette) {
                return new JsonResponse(['erreur' => 'Dette non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $this->detteService->supprimerDette($dette);

            return new JsonResponse([
                'message' => 'Dette supprimée avec succès'
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
     * Enregistrer un paiement pour une dette
     */
    #[Route('/{id}/paiements', name: 'enregistrer_paiement', methods: ['POST'])]
    public function enregistrerPaiement(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $dette = $this->detteService->recupererDette($user, $id);
            if (!$dette) {
                return new JsonResponse(['erreur' => 'Dette non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $donnees = json_decode($request->getContent(), true);
            $paiement = $this->detteService->enregistrerPaiement($dette, $donnees);

            return new JsonResponse([
                'message' => 'Paiement enregistré avec succès',
                'paiement' => $this->serialiserPaiement($paiement),
                'dette' => $this->serialiserDette($dette)
            ], Response::HTTP_CREATED);

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
     * Récupérer les paiements d'une dette
     */
    #[Route('/{id}/paiements', name: 'paiements', methods: ['GET'])]
    public function listerPaiements(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $dette = $this->detteService->recupererDette($user, $id);
            if (!$dette) {
                return new JsonResponse(['erreur' => 'Dette non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $paiements = $dette->getPaiements()->toArray();
            usort($paiements, fn($a, $b) => $b->getDatePaiement() <=> $a->getDatePaiement());

            return new JsonResponse([
                'paiements' => array_map([$this, 'serialiserPaiement'], $paiements)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les dettes en retard
     */
    #[Route('/en-retard', name: 'en_retard', methods: ['GET'])]
    public function dettesEnRetard(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $dettes = $this->detteService->recupererDettesEnRetard($user);

            return new JsonResponse([
                'dettes' => array_map([$this, 'serialiserDette'], $dettes)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les dettes avec échéance proche
     */
    #[Route('/echeance-proche', name: 'echeance_proche', methods: ['GET'])]
    public function dettesEcheanceProche(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $jours = (int) $request->query->get('jours', 7);
            $dettes = $this->detteService->recupererDettesEcheanceProche($user, $jours);

            return new JsonResponse([
                'dettes' => array_map([$this, 'serialiserDette'], $dettes)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer le résumé des dettes
     */
    #[Route('/resume', name: 'resume', methods: ['GET'])]
    public function resumeDettes(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $resume = $this->detteService->calculerResumeDettes($user);

            return new JsonResponse($resume);

        } catch (\Exception $e) {
            return new JsonResponse([
                'erreur' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour les statuts des dettes
     */
    #[Route('/mettre-a-jour-statuts', name: 'mettre_a_jour_statuts', methods: ['POST'])]
    public function mettreAJourStatuts(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['erreur' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $compteur = $this->detteService->mettreAJourStatutsDettes($user);

            return new JsonResponse([
                'message' => 'Statuts mis à jour avec succès',
                'dettes_modifiees' => $compteur
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

        if ($request->query->has('typeDette')) {
            $filtres['typeDette'] = $request->query->get('typeDette');
        }
        if ($request->query->has('statutDette')) {
            $filtres['statutDette'] = $request->query->get('statutDette');
        }
        if ($request->query->has('contact_id')) {
            $filtres['contact_id'] = (int) $request->query->get('contact_id');
        }
        if ($request->query->has('compte_id')) {
            $filtres['compte_id'] = (int) $request->query->get('compte_id');
        }
        if ($request->query->has('date_debut')) {
            $filtres['date_debut'] = new \DateTime($request->query->get('date_debut'));
        }
        if ($request->query->has('date_fin')) {
            $filtres['date_fin'] = new \DateTime($request->query->get('date_fin'));
        }

        return $filtres;
    }

    private function serialiserDette(Dette $dette): array
    {
        return [
            'id' => $dette->getId(),
            'typeDette' => $dette->getTypeDette(),
            'typeDetteLabel' => $dette->getTypeDetteLabel(),
            'montantPrincipal' => $dette->getMontantPrincipal(),
            'montantPrincipalFormatted' => number_format((float) $dette->getMontantPrincipal(), 0, ',', ' ') . ' F',
            'tauxInteret' => $dette->getTauxInteret(),
            'montantInterets' => $dette->getMontantInterets(),
            'montantInteretsFormatted' => $dette->getMontantInterets() ? number_format((float) $dette->getMontantInterets(), 0, ',', ' ') . ' F' : '0 F',
            'montantTotal' => $dette->getMontantTotal(),
            'montantTotalFormatted' => $dette->getMontantTotal() ? number_format((float) $dette->getMontantTotal(), 0, ',', ' ') . ' F' : '0 F',
            'montantEffectif' => $dette->getMontantEffectif(),
            'montantEffectifFormatted' => number_format((float) $dette->getMontantEffectif(), 0, ',', ' ') . ' F',
            'montantRestant' => $dette->calculerMontantRestant(),
            'montantRestantFormatted' => number_format((float) $dette->calculerMontantRestant(), 0, ',', ' ') . ' F',
            'statutDette' => $dette->getStatutDette(),
            'statutDetteLabel' => $dette->getStatutDetteLabel(),
            'date' => $dette->getDate()->format('Y-m-d'),
            'dateEcheance' => $dette->getDateEcheance()?->format('Y-m-d'),
            'dateDernierPaiement' => $dette->getDateDernierPaiement()?->format('Y-m-d'),
            'description' => $dette->getDescription(),
            'notes' => $dette->getNotes(),
            'estEnRetard' => $dette->estEnRetard(),
            'echeanceProche' => $dette->echeanceProche(),
            'contact' => $dette->getContact() ? [
                'id' => $dette->getContact()->getId(),
                'nom' => $dette->getContact()->getNom(),
                'telephone' => $dette->getContact()->getTelephone()
            ] : null,
            'compte' => $dette->getCompte() ? [
                'id' => $dette->getCompte()->getId(),
                'nom' => $dette->getCompte()->getNom(),
                'type' => $dette->getCompte()->getType()
            ] : null,
            'categorie' => $dette->getCategorie() ? [
                'id' => $dette->getCategorie()->getId(),
                'nom' => $dette->getCategorie()->getNom(),
                'type' => $dette->getCategorie()->getType()
            ] : null
        ];
    }

    private function serialiserDetteComplete(Dette $dette): array
    {
        $detteData = $this->serialiserDette($dette);
        
        // Ajouter les paiements
        $paiements = $dette->getPaiements()->toArray();
        usort($paiements, fn($a, $b) => $b->getDatePaiement() <=> $a->getDatePaiement());
        
        $detteData['paiements'] = array_map([$this, 'serialiserPaiement'], $paiements);
        
        return $detteData;
    }

    private function serialiserPaiement(PaiementDette $paiement): array
    {
        return [
            'id' => $paiement->getId(),
            'montant' => $paiement->getMontant(),
            'montantFormatted' => number_format((float) $paiement->getMontant(), 0, ',', ' ') . ' F',
            'montantPrincipal' => $paiement->getMontantPrincipal(),
            'montantPrincipalFormatted' => $paiement->getMontantPrincipal() ? number_format((float) $paiement->getMontantPrincipal(), 0, ',', ' ') . ' F' : '0 F',
            'montantInteret' => $paiement->getMontantInteret(),
            'montantInteretFormatted' => $paiement->getMontantInteret() ? number_format((float) $paiement->getMontantInteret(), 0, ',', ' ') . ' F' : '0 F',
            'typePaiement' => $paiement->getTypePaiement(),
            'typePaiementLabel' => $paiement->getTypePaiementLabel(),
            'statutPaiement' => $paiement->getStatutPaiement(),
            'statutPaiementLabel' => $paiement->getStatutPaiementLabel(),
            'datePaiement' => $paiement->getDatePaiement()->format('Y-m-d'),
            'commentaire' => $paiement->getCommentaire(),
            'reference' => $paiement->getReference(),
            'dateCreation' => $paiement->getDateCreation()->format('Y-m-d H:i:s')
        ];
    }
}
