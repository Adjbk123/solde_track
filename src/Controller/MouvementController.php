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
        private NotificationService $notificationService
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
        $this->sendMotivationIfNeeded($user, 'depense');

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
        $this->sendIncomeNotification($user, $entree);

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
        $mouvementsParType = [];
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
            if (!isset($mouvementsParType[$typeMouvement])) {
                $mouvementsParType[$typeMouvement] = 0;
            }
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
     * Envoie une notification d'alerte de revenu
     */
    private function sendIncomeNotification(User $user, Entree $entree): void
    {
        try {
            $data = [
                'amount' => $entree->getMontantTotal(),
                'source' => $entree->getCategorie() ? $entree->getCategorie()->getNom() : 'une source',
                'currency' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF'
            ];
            
            $this->notificationService->sendNotification($user, 'INCOME_ALERT', $data);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la création du mouvement
            error_log('Erreur lors de l\'envoi de notification de revenu: ' . $e->getMessage());
        }
    }
}