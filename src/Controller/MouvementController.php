<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Entity\Depense;
use App\Entity\Entree;
use App\Entity\Dette;
use App\Entity\Don;
use App\Entity\Categorie;
use App\Entity\Projet;
use App\Entity\Contact;
use App\Entity\User;
use App\Entity\Compte;
use App\Service\UserDeviseService;
use App\Service\NotificationService;
use App\Service\PushNotificationService;
use App\Service\DebtManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/mouvements', name: 'api_mouvements_')]
class MouvementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserDeviseService $userDeviseService,
        private NotificationService $notificationService,
        private PushNotificationService $pushNotificationService,
        private DebtManagementService $debtManagementService
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type');
        $projetId = $request->query->get('projet_id');
        $categorieId = $request->query->get('categorie_id');
        $statut = $request->query->get('statut');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.date', 'DESC');

        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }
        if ($projetId) {
            $qb->andWhere('m.projet = :projet')->setParameter('projet', $projetId);
        }
        if ($categorieId) {
            $qb->andWhere('m.categorie = :categorie')->setParameter('categorie', $categorieId);
        }
        if ($statut) {
            $qb->andWhere('m.statut = :statut')->setParameter('statut', $statut);
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $mouvements = $qb->getQuery()->getResult();

        $data = [];
        foreach ($mouvements as $mouvement) {
            $data[] = $this->serializeMouvement($mouvement, $user);
        }

        return new JsonResponse([
            'mouvements' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($data)
            ]
        ]);
    }

    #[Route('/depenses', name: 'create_depense', methods: ['POST'])]
    #[Route('/sorties', name: 'create_sortie', methods: ['POST'])]
    public function createDepense(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        if (!isset($data['montantTotal']) || !isset($data['categorie_id'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Montant total et catégorie sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la catégorie
        $categorie = $this->entityManager->getRepository(Categorie::class)->find($data['categorie_id']);
        if (!$categorie || $categorie->getUser() !== $user) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Créer la dépense
        $depense = new Depense();
        $depense->setUser($user);
        $depense->setMontantTotal($data['montantTotal']);
        $depense->setCategorie($categorie);
        $depense->setDescription($data['description'] ?? null);
        $depense->setLieu($data['lieu'] ?? null);
        $depense->setMethodePaiement($data['methodePaiement'] ?? null);
        $depense->setRecu($data['recu'] ?? null);

        if (isset($data['projet_id'])) {
            $projet = $this->entityManager->getRepository(Projet::class)->find($data['projet_id']);
            if ($projet && $projet->getUser() === $user) {
                $depense->setProjet($projet);
            }
        }

        if (isset($data['contact_id'])) {
            $contact = $this->entityManager->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact && $contact->getUser() === $user) {
                $depense->setContact($contact);
            }
        }

        if (isset($data['compte_id'])) {
            $compte = $this->entityManager->getRepository(Compte::class)->find($data['compte_id']);
            if ($compte && $compte->getUser() === $user) {
                $depense->setCompte($compte);
            }
        }

        if (isset($data['date'])) {
            $depense->setDate(new \DateTime($data['date']));
        }

        // Valider l'entité
        $errors = $this->validator->validate($depense);
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

        $this->entityManager->persist($depense);
        
        // Mettre à jour le solde du compte AVANT le flush
        $this->updateCompteSolde($depense);
        
        $this->entityManager->flush();

        // Envoyer une notification de motivation si c'est la première dépense du jour
        try {
            $this->sendMotivationIfNeeded($user, 'depense');
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création
            error_log('Erreur notification motivation: ' . $e->getMessage());
        }

        return new JsonResponse([
            'message' => 'Dépense créée avec succès',
            'mouvement' => $this->serializeMouvement($depense, $user)
        ], Response::HTTP_CREATED);
    }

    #[Route('/entrees', name: 'create_entree', methods: ['POST'])]
    public function createEntree(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['montantTotal']) || !isset($data['categorie_id'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Montant total et catégorie sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $this->entityManager->getRepository(Categorie::class)->find($data['categorie_id']);
        if (!$categorie || $categorie->getUser() !== $user) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $entree = new Entree();
        $entree->setUser($user);
        $entree->setMontantTotal($data['montantTotal']);
        $entree->setCategorie($categorie);
        $entree->setDescription($data['description'] ?? null);
        $entree->setSource($data['source'] ?? null);
        $entree->setMethode($data['methode'] ?? null);

        if (isset($data['projet_id'])) {
            $projet = $this->entityManager->getRepository(Projet::class)->find($data['projet_id']);
            if ($projet && $projet->getUser() === $user) {
                $entree->setProjet($projet);
            }
        }

        if (isset($data['contact_id'])) {
            $contact = $this->entityManager->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact && $contact->getUser() === $user) {
                $entree->setContact($contact);
            }
        }

        if (isset($data['compte_id'])) {
            $compte = $this->entityManager->getRepository(Compte::class)->find($data['compte_id']);
            if ($compte && $compte->getUser() === $user) {
                $entree->setCompte($compte);
            }
        }

        if (isset($data['date'])) {
            $entree->setDate(new \DateTime($data['date']));
        }

        $errors = $this->validator->validate($entree);
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

        $this->entityManager->persist($entree);
        
        // Mettre à jour le solde du compte AVANT le flush
        $this->updateCompteSolde($entree);
        
        $this->entityManager->flush();

        // Envoyer une notification d'alerte de revenu
        try {
            if ($user->getFcmToken()) {
                $currency = $user->getDevise() ? $user->getDevise()->getCode() : 'XOF';
                $this->pushNotificationService->sendIncomeNotification(
                    $user->getFcmToken(),
                    $user->getNom() ?? $user->getEmail(),
                    (float) $entree->getMontantTotal(),
                    $currency
                );
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création
            error_log('Erreur notification revenu: ' . $e->getMessage());
        }

        return new JsonResponse([
            'message' => 'Entrée créée avec succès',
            'mouvement' => $this->serializeMouvement($entree, $user)
        ], Response::HTTP_CREATED);
    }

    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    public function getStatistiques(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');
        $type = $request->query->get('type');

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user);

        if ($debut) {
            $qb->andWhere('m.date >= :debut')->setParameter('debut', new \DateTime($debut));
        }
        if ($fin) {
            $qb->andWhere('m.date <= :fin')->setParameter('fin', new \DateTime($fin));
        }
        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        $mouvements = $qb->getQuery()->getResult();

        $totalDepenses = 0;
        $totalEntrees = 0;
        $totalDettes = 0;
        $totalDons = 0;
        $mouvementsParType = [
            'depense' => 0,
            'entree' => 0,
            'dette_a_payer' => 0,
            'dette_a_recevoir' => 0,
            'don' => 0
        ];
        $mouvementsParCategorie = [];

        foreach ($mouvements as $mouvement) {
            $montant = (float) $mouvement->getMontantEffectif();
            $typeMouvement = $mouvement->getType();
            $categorieNom = $mouvement->getCategorie()->getNom();

            switch ($typeMouvement) {
                case 'depense':
                    $totalDepenses += $montant;
                    break;
                case 'entree':
                    $totalEntrees += $montant;
                    break;
                case 'dette_a_payer':
                case 'dette_a_recevoir':
                    $totalDettes += $montant;
                    break;
                case 'don':
                    $totalDons += $montant;
                    break;
            }

            // Grouper par type
            $mouvementsParType[$typeMouvement] += $montant;

            // Grouper par catégorie
            if (!isset($mouvementsParCategorie[$categorieNom])) {
                $mouvementsParCategorie[$categorieNom] = 0;
            }
            $mouvementsParCategorie[$categorieNom] += $montant;
        }

        return new JsonResponse([
            'totalDepenses' => number_format($totalDepenses, 2, '.', ''),
            'totalEntrees' => number_format($totalEntrees, 2, '.', ''),
            'totalDettes' => number_format($totalDettes, 2, '.', ''),
            'totalDons' => number_format($totalDons, 2, '.', ''),
            'soldeNet' => number_format($totalEntrees - $totalDepenses, 2, '.', ''),
            'parType' => $mouvementsParType,
            'parCategorie' => $mouvementsParCategorie
        ]);
    }

    #[Route('/recents', name: 'recents', methods: ['GET'])]
    public function getRecents(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

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

        $data = [];
        foreach ($mouvements as $mouvement) {
            $data[] = $this->serializeMouvement($mouvement, $user);
        }

        return new JsonResponse(['mouvements' => $data]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvementId = (int) $id;
        $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($mouvementId);
        if (!$mouvement || $mouvement->getUser() !== $user) {
            return new JsonResponse(['error' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'mouvement' => $this->serializeMouvement($mouvement, $user)
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvementId = (int) $id;
        $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($mouvementId);
        if (!$mouvement || $mouvement->getUser() !== $user) {
            return new JsonResponse(['error' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Mettre à jour les champs modifiables
        if (isset($data['montantTotal'])) {
            $mouvement->setMontantTotal($data['montantTotal']);
        }
        if (isset($data['description'])) {
            $mouvement->setDescription($data['description']);
        }
        if (isset($data['date'])) {
            $mouvement->setDate(new \DateTime($data['date']));
        }

        $errors = $this->validator->validate($mouvement);
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
            'message' => 'Mouvement mis à jour avec succès',
            'mouvement' => $this->serializeMouvement($mouvement, $user)
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $mouvementId = (int) $id;
        $mouvement = $this->entityManager->getRepository(Mouvement::class)->find($mouvementId);
        if (!$mouvement || $mouvement->getUser() !== $user) {
            return new JsonResponse(['error' => 'Mouvement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($mouvement);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Mouvement supprimé avec succès'
        ]);
    }

    #[Route('/dettes', name: 'create_dette', methods: ['POST'])]
    public function createDette(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['montantTotal']) || !isset($data['categorie_id'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Montant total et catégorie sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $this->entityManager->getRepository(Categorie::class)->find($data['categorie_id']);
        if (!$categorie || $categorie->getUser() !== $user) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $dette = new Dette();
        $dette->setUser($user);
        $dette->setMontantTotal($data['montantTotal']);
        $dette->setCategorie($categorie);
        $dette->setDescription($data['description'] ?? null);
        $dette->setEcheance(isset($data['echeance']) ? new \DateTime($data['echeance']) : null);
        $dette->setTaux($data['taux'] ?? null);
        $dette->setMontantRest($data['montantTotal']); // Initialement, le montant restant = montant total

        if (isset($data['projet_id'])) {
            $projet = $this->entityManager->getRepository(Projet::class)->find($data['projet_id']);
            if ($projet && $projet->getUser() === $user) {
                $dette->setProjet($projet);
            }
        }

        if (isset($data['contact_id'])) {
            $contact = $this->entityManager->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact && $contact->getUser() === $user) {
                $dette->setContact($contact);
            }
        }

        if (isset($data['compte_id'])) {
            $compte = $this->entityManager->getRepository(Compte::class)->find($data['compte_id']);
            if ($compte && $compte->getUser() === $user) {
                $dette->setCompte($compte);
            }
        }

        if (isset($data['date'])) {
            $dette->setDate(new \DateTime($data['date']));
        }

        $errors = $this->validator->validate($dette);
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

        $this->entityManager->persist($dette);
        
        // Mettre à jour le solde du compte AVANT le flush
        $this->updateCompteSolde($dette);
        
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Dette créée avec succès',
            'mouvement' => $this->serializeMouvement($dette, $user)
        ], Response::HTTP_CREATED);
    }

    #[Route('/dons', name: 'create_don', methods: ['POST'])]
    public function createDon(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['montantTotal']) || !isset($data['categorie_id'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Montant total et catégorie sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $categorie = $this->entityManager->getRepository(Categorie::class)->find($data['categorie_id']);
        if (!$categorie || $categorie->getUser() !== $user) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $don = new Don();
        $don->setUser($user);
        $don->setMontantTotal($data['montantTotal']);
        $don->setCategorie($categorie);
        $don->setDescription($data['description'] ?? null);
        $don->setOccasion($data['occasion'] ?? null);

        if (isset($data['projet_id'])) {
            $projet = $this->entityManager->getRepository(Projet::class)->find($data['projet_id']);
            if ($projet && $projet->getUser() === $user) {
                $don->setProjet($projet);
            }
        }

        if (isset($data['contact_id'])) {
            $contact = $this->entityManager->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact && $contact->getUser() === $user) {
                $don->setContact($contact);
            }
        }

        if (isset($data['compte_id'])) {
            $compte = $this->entityManager->getRepository(Compte::class)->find($data['compte_id']);
            if ($compte && $compte->getUser() === $user) {
                $don->setCompte($compte);
            }
        }

        if (isset($data['date'])) {
            $don->setDate(new \DateTime($data['date']));
        }

        $errors = $this->validator->validate($don);
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

        $this->entityManager->persist($don);
        
        // Mettre à jour le solde du compte AVANT le flush
        $this->updateCompteSolde($don);
        
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Don créé avec succès',
            'mouvement' => $this->serializeMouvement($don, $user)
        ], Response::HTTP_CREATED);
    }

    private function serializeMouvement(Mouvement $mouvement, User $user = null): array
    {
        $data = [
            'id' => $mouvement->getId(),
            'type' => $mouvement->getType(),
            'typeLabel' => $mouvement->getTypeLabel(),
            'montantTotal' => $mouvement->getMontantTotal(),
            'montantEffectif' => $mouvement->getMontantEffectif(),
            'montantRestant' => $mouvement->getMontantRestant(),
            'statut' => $mouvement->getStatut(),
            'statutLabel' => $mouvement->getStatutLabel(),
            'date' => $mouvement->getDate()->format('Y-m-d H:i:s'),
            'description' => $mouvement->getDescription(),
        ];

        if ($user) {
            $data['montantTotalFormatted'] = $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantTotal());
            $data['montantEffectifFormatted'] = $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantEffectif());
            $data['montantRestantFormatted'] = $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantRestant());
        }

        // Ajouter les relations si elles existent
        if ($mouvement->getCategorie()) {
            $data['categorie'] = [
                'id' => $mouvement->getCategorie()->getId(),
                'nom' => $mouvement->getCategorie()->getNom(),
                'type' => $mouvement->getCategorie()->getType(),
                'typeLabel' => $mouvement->getCategorie()->getTypeLabel(),
            ];
        }

        if ($mouvement->getProjet()) {
            $data['projet'] = [
                'id' => $mouvement->getProjet()->getId(),
                'nom' => $mouvement->getProjet()->getNom(),
                'description' => $mouvement->getProjet()->getDescription(),
                'budgetPrevu' => $mouvement->getProjet()->getBudgetPrevu(),
                'budgetPrevuFormatted' => $user ? $this->userDeviseService->formatAmount($user, (float) $mouvement->getProjet()->getBudgetPrevu()) : null,
            ];
        }

        if ($mouvement->getContact()) {
            $data['contact'] = [
                'id' => $mouvement->getContact()->getId(),
                'nom' => $mouvement->getContact()->getNom(),
                'telephone' => $mouvement->getContact()->getTelephone(),
                'email' => $mouvement->getContact()->getEmail(),
                'source' => $mouvement->getContact()->getSource(),
            ];
        }

        if ($mouvement->getCompte()) {
            $data['compte'] = [
                'id' => $mouvement->getCompte()->getId(),
                'nom' => $mouvement->getCompte()->getNom(),
                'description' => $mouvement->getCompte()->getDescription(),
                'soldeActuel' => $mouvement->getCompte()->getSoldeActuel(),
                'soldeActuelFormatted' => $user ? $this->userDeviseService->formatAmount($user, (float) $mouvement->getCompte()->getSoldeActuel()) : null,
                'devise' => $mouvement->getCompte()->getDevise()->getCode(),
            ];
        }

        // Ajouter les champs spécifiques selon le type
        if ($mouvement instanceof Depense) {
            $data['lieu'] = $mouvement->getLieu();
            $data['methodePaiement'] = $mouvement->getMethodePaiement();
            $data['methodePaiementLabel'] = $mouvement->getMethodePaiementLabel();
            $data['recu'] = $mouvement->getRecu();
        } elseif ($mouvement instanceof Entree) {
            $data['source'] = $mouvement->getSource();
            $data['methode'] = $mouvement->getMethode();
            $data['methodeLabel'] = $mouvement->getMethodeLabel();
        } elseif ($mouvement instanceof Dette) {
            $data['echeance'] = $mouvement->getEcheance()?->format('Y-m-d');
            $data['taux'] = $mouvement->getTaux();
            $data['montantRest'] = $mouvement->getMontantRest();
            $data['montantInterets'] = $mouvement->getMontantInterets();
            $data['enRetard'] = $mouvement->isEnRetard();
            if ($user) {
                $data['montantRestFormatted'] = $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantRest());
                $data['montantInteretsFormatted'] = $this->userDeviseService->formatAmount($user, (float) $mouvement->getMontantInterets());
            }
        } elseif ($mouvement instanceof Don) {
            $data['occasion'] = $mouvement->getOccasion();
        }

        return $data;
    }

    /**
     * Met à jour le solde du compte associé au mouvement
     */
    private function updateCompteSolde(Mouvement $mouvement): void
    {
        if (!$mouvement->getCompte()) {
            return; // Pas de compte associé
        }

        $compte = $mouvement->getCompte();
        $montant = (float) $mouvement->getMontantTotal();
        $soldeActuel = (float) $compte->getSoldeActuel();

        // Calculer le nouveau solde selon le type de mouvement
        switch ($mouvement->getType()) {
            case 'entree':
            case 'dette_a_recevoir':
                // Les entrées et dettes à recevoir augmentent le solde
                $nouveauSolde = $soldeActuel + $montant;
                $mouvement->setMontantEffectif($mouvement->getMontantTotal());
                break;
            
            case 'depense':
            case 'dette_a_payer':
            case 'don':
                // Les dépenses, dettes à payer et dons diminuent le solde
                $nouveauSolde = $soldeActuel - $montant;
                $mouvement->setMontantEffectif($mouvement->getMontantTotal());
                break;
            
            default:
                return; // Type non reconnu
        }

        // Mettre à jour le solde du compte
        $compte->setSoldeActuel(number_format($nouveauSolde, 2, '.', ''));
        $compte->setDateModification(new \DateTime());
    }

    /**
     * Envoie une notification de motivation si nécessaire
     */
    private function sendMotivationIfNeeded(User $user, string $type): void
    {
        try {
            // Vérifier si c'est la première transaction du jour
            $today = new \DateTime();
            $mouvementRepository = $this->entityManager->getRepository(Mouvement::class);
            
            $todayMovements = $mouvementRepository->createQueryBuilder('m')
                ->where('m.user = :user')
                ->andWhere('DATE(m.date) = :today')
                ->setParameter('user', $user)
                ->setParameter('today', $today->format('Y-m-d'))
                ->getQuery()
                ->getResult();
            
            // Si c'est la première transaction du jour, envoyer une notification de motivation
            if (count($todayMovements) === 1) {
                $this->notificationService->sendMotivationNotification($user);
            }
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création du mouvement
            error_log('Erreur lors de l\'envoi de notification de motivation: ' . $e->getMessage());
        }
    }

    /**
     * Récupérer les mouvements du jour
     */
    #[Route('/today', name: 'today', methods: ['GET'])]
    public function getTodayMovements(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type'); // depense/sortie, entree, dette, don
        
        // Support de l'ancien terme "depense" et du nouveau "sortie"
        if ($type === 'sortie') {
            $type = 'depense';
        }
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.date >= :start')
            ->andWhere('m.date < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->orderBy('m.date', 'DESC');

        if ($type) {
            $qb->andWhere('m INSTANCE OF :type');
            $typeClass = match($type) {
                'depense' => Depense::class,
                'entree' => Entree::class,
                'dette' => Dette::class,
                'don' => Don::class,
                default => null
            };
            if ($typeClass) {
                $qb->setParameter('type', $typeClass);
            }
        }

        $mouvements = $qb->getQuery()->getResult();

        return new JsonResponse([
            'date' => $today->format('Y-m-d'),
            'mouvements' => array_map(fn($m) => $this->serializeMouvement($m, $user), $mouvements),
            'total' => count($mouvements)
        ]);
    }

    /**
     * Récupérer les mouvements d'une période
     */
    #[Route('/period', name: 'period', methods: ['GET'])]
    public function getPeriodMovements(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        if (!$startDate || !$endDate) {
            return new JsonResponse(['error' => 'start_date et end_date sont requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $start = new \DateTime($startDate);
            $start->setTime(0, 0, 0);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Format de date invalide'], Response::HTTP_BAD_REQUEST);
        }

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.date >= :start')
            ->andWhere('m.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('m.date', 'DESC');

        if ($type) {
            $qb->andWhere('m INSTANCE OF :type');
            $typeClass = match($type) {
                'depense' => Depense::class,
                'entree' => Entree::class,
                'dette' => Dette::class,
                'don' => Don::class,
                default => null
            };
            if ($typeClass) {
                $qb->setParameter('type', $typeClass);
            }
        }

        $mouvements = $qb->getQuery()->getResult();

        return new JsonResponse([
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'mouvements' => array_map(fn($m) => $this->serializeMouvement($m, $user), $mouvements),
            'total' => count($mouvements)
        ]);
    }

    /**
     * Récupérer les mouvements de la semaine
     */
    #[Route('/week', name: 'week', methods: ['GET'])]
    public function getWeekMovements(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type');
        $weekOffset = (int) $request->query->get('week_offset', 0); // 0 = cette semaine, -1 = semaine dernière, etc.

        $today = new \DateTime();
        $today->modify("+{$weekOffset} weeks");
        
        // Début de la semaine (lundi)
        $startOfWeek = clone $today;
        $startOfWeek->modify('monday this week')->setTime(0, 0, 0);
        
        // Fin de la semaine (dimanche)
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('sunday this week')->setTime(23, 59, 59);

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.date >= :start')
            ->andWhere('m.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->orderBy('m.date', 'DESC');

        if ($type) {
            $qb->andWhere('m INSTANCE OF :type');
            $typeClass = match($type) {
                'depense' => Depense::class,
                'entree' => Entree::class,
                'dette' => Dette::class,
                'don' => Don::class,
                default => null
            };
            if ($typeClass) {
                $qb->setParameter('type', $typeClass);
            }
        }

        $mouvements = $qb->getQuery()->getResult();

        return new JsonResponse([
            'week_start' => $startOfWeek->format('Y-m-d'),
            'week_end' => $endOfWeek->format('Y-m-d'),
            'week_offset' => $weekOffset,
            'mouvements' => array_map(fn($m) => $this->serializeMouvement($m, $user), $mouvements),
            'total' => count($mouvements)
        ]);
    }

    /**
     * Récupérer les mouvements du mois
     */
    #[Route('/month', name: 'month', methods: ['GET'])]
    public function getMonthMovements(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $type = $request->query->get('type');
        $monthOffset = (int) $request->query->get('month_offset', 0); // 0 = ce mois, -1 = mois dernier, etc.

        $today = new \DateTime();
        $today->modify("+{$monthOffset} months");
        
        // Début du mois
        $startOfMonth = clone $today;
        $startOfMonth->modify('first day of this month')->setTime(0, 0, 0);
        
        // Fin du mois
        $endOfMonth = clone $today;
        $endOfMonth->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.date >= :start')
            ->andWhere('m.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('m.date', 'DESC');

        if ($type) {
            $qb->andWhere('m INSTANCE OF :type');
            $typeClass = match($type) {
                'depense' => Depense::class,
                'entree' => Entree::class,
                'dette' => Dette::class,
                'don' => Don::class,
                default => null
            };
            if ($typeClass) {
                $qb->setParameter('type', $typeClass);
            }
        }

        $mouvements = $qb->getQuery()->getResult();

        return new JsonResponse([
            'month' => $startOfMonth->format('Y-m'),
            'month_start' => $startOfMonth->format('Y-m-d'),
            'month_end' => $endOfMonth->format('Y-m-d'),
            'month_offset' => $monthOffset,
            'mouvements' => array_map(fn($m) => $this->serializeMouvement($m, $user), $mouvements),
            'total' => count($mouvements)
        ]);
    }

    /**
     * Récupérer les dépenses du jour
     */
    #[Route('/depenses/today', name: 'depenses_today', methods: ['GET'])]
    #[Route('/sorties/today', name: 'sorties_today', methods: ['GET'])]
    public function getTodayDepenses(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        $depenses = $this->entityManager->getRepository(Depense::class)
            ->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('d.date >= :start')
            ->andWhere('d.date < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->orderBy('d.date', 'DESC')
            ->getQuery()
            ->getResult();

        $totalDepenses = array_sum(array_map(fn($d) => (float) $d->getMontantTotal(), $depenses));

        return new JsonResponse([
            'date' => $today->format('Y-m-d'),
            'depenses' => array_map(fn($d) => $this->serializeMouvement($d, $user), $depenses),
            'total_count' => count($depenses),
            'total_amount' => $totalDepenses,
            'total_amount_formatted' => number_format($totalDepenses) . ' ' . ($user->getDevise() ? $user->getDevise()->getCode() : 'XOF')
        ]);
    }

    /**
     * Récupérer les revenus du jour
     */
    #[Route('/entrees/today', name: 'entrees_today', methods: ['GET'])]
    public function getTodayEntrees(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        $entrees = $this->entityManager->getRepository(Entree::class)
            ->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.date >= :start')
            ->andWhere('e.date < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();

        $totalEntrees = array_sum(array_map(fn($e) => (float) $e->getMontantTotal(), $entrees));

        return new JsonResponse([
            'date' => $today->format('Y-m-d'),
            'entrees' => array_map(fn($e) => $this->serializeMouvement($e, $user), $entrees),
            'total_count' => count($entrees),
            'total_amount' => $totalEntrees,
            'total_amount_formatted' => number_format($totalEntrees) . ' ' . ($user->getDevise() ? $user->getDevise()->getCode() : 'XOF')
        ]);
    }

    /**
     * Gestion des dettes - obtenir les catégories compatibles
     */
    #[Route('/debt-categories/{movementType}', name: 'debt_categories', methods: ['GET'])]
    public function getDebtCategories(string $movementType): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Valider le type de mouvement
        if (!in_array($movementType, [Mouvement::TYPE_DETTE_A_PAYER, Mouvement::TYPE_DETTE_A_RECEVOIR])) {
            return new JsonResponse(['error' => 'Type de mouvement de dette invalide'], Response::HTTP_BAD_REQUEST);
        }

        $categories = $this->debtManagementService->getCompatibleCategoriesForDebtMovement($user, $movementType);
        $suggestedCategories = $this->debtManagementService->getSuggestedCategoriesForDebtMovement($movementType);

        $data = [];
        foreach ($categories as $categorie) {
            $data[] = [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNom(),
                'type' => $categorie->getType(),
                'typeLabel' => $categorie->getTypeLabel(),
                'nombreMouvements' => $categorie->getMouvements()->count()
            ];
        }

        return new JsonResponse([
            'movementType' => $movementType,
            'categories' => $data,
            'suggestedCategories' => $suggestedCategories
        ]);
    }

    /**
     * Obtenir le solde des dettes
     */
    #[Route('/debt-balance', name: 'debt_balance', methods: ['GET'])]
    public function getDebtBalance(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $balance = $this->debtManagementService->calculateDebtBalance($user);
        
        return new JsonResponse([
            'dettes_a_payer' => number_format($balance['dettes_a_payer'], 2, '.', ''),
            'dettes_a_recevoir' => number_format($balance['dettes_a_recevoir'], 2, '.', ''),
            'solde_dettes' => number_format($balance['solde_dettes'], 2, '.', ''),
            'net_positive' => $balance['net_positive'],
            'devise' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF'
        ]);
    }

}