<?php

namespace App\Service;

use App\Entity\DepensePrevue;
use App\Entity\User;
use App\Entity\Mouvement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class DepensePrevueService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * Crée une nouvelle dépense prévue
     */
    public function creerDepensePrevue(User $user, array $donnees): DepensePrevue
    {
        $depensePrevue = new DepensePrevue();
        $depensePrevue->setUser($user);
        $depensePrevue->setNom($donnees['nom']);
        $depensePrevue->setDescription($donnees['description'] ?? null);

        // Gestion flexible du budget
        if (isset($donnees['budgetPrevu']) && $donnees['budgetPrevu']) {
            $depensePrevue->setBudgetPrevu($donnees['budgetPrevu']);
            $depensePrevue->setTypeBudget($donnees['typeBudget'] ?? DepensePrevue::TYPE_BUDGET_ESTIME);
        } else {
            // Pas de budget défini
            $depensePrevue->setTypeBudget(DepensePrevue::TYPE_BUDGET_INCONNU);
        }

        // Dates prévues (optionnelles)
        if (isset($donnees['dateDebutPrevue'])) {
            $depensePrevue->setDateDebutPrevue(new \DateTime($donnees['dateDebutPrevue']));
        }
        if (isset($donnees['dateFinPrevue'])) {
            $depensePrevue->setDateFinPrevue(new \DateTime($donnees['dateFinPrevue']));
        }

        // Validation
        $errors = $this->validator->validate($depensePrevue);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException('Erreurs de validation: ' . implode(', ', $errorMessages));
        }

        $this->entityManager->persist($depensePrevue);
        $this->entityManager->flush();

        return $depensePrevue;
    }

    /**
     * Met à jour une dépense prévue existante
     */
    public function mettreAJourDepensePrevue(DepensePrevue $depensePrevue, array $donnees): DepensePrevue
    {
        if (isset($donnees['nom'])) {
            $depensePrevue->setNom($donnees['nom']);
        }
        if (isset($donnees['description'])) {
            $depensePrevue->setDescription($donnees['description']);
        }

        // Gestion flexible du budget
        if (isset($donnees['budgetPrevu'])) {
            if ($donnees['budgetPrevu']) {
                $depensePrevue->setBudgetPrevu($donnees['budgetPrevu']);
                $depensePrevue->setTypeBudget($donnees['typeBudget'] ?? DepensePrevue::TYPE_BUDGET_ESTIME);
            } else {
                // Supprimer le budget
                $depensePrevue->setBudgetPrevu(null);
                $depensePrevue->setTypeBudget(DepensePrevue::TYPE_BUDGET_INCONNU);
            }
        }

        if (isset($donnees['typeBudget'])) {
            $depensePrevue->setTypeBudget($donnees['typeBudget']);
        }

        if (isset($donnees['dateDebutPrevue'])) {
            $depensePrevue->setDateDebutPrevue($donnees['dateDebutPrevue'] ? new \DateTime($donnees['dateDebutPrevue']) : null);
        }
        if (isset($donnees['dateFinPrevue'])) {
            $depensePrevue->setDateFinPrevue($donnees['dateFinPrevue'] ? new \DateTime($donnees['dateFinPrevue']) : null);
        }

        // Validation
        $errors = $this->validator->validate($depensePrevue);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \InvalidArgumentException('Erreurs de validation: ' . implode(', ', $errorMessages));
        }

        $this->entityManager->flush();
        return $depensePrevue;
    }

    /**
     * Supprime une dépense prévue
     */
    public function supprimerDepensePrevue(DepensePrevue $depensePrevue): void
    {
        // Vérifier s'il y a des mouvements associés
        if ($depensePrevue->getMouvements()->count() > 0) {
            throw new \InvalidArgumentException('Impossible de supprimer une dépense prévue avec des mouvements associés');
        }

        $this->entityManager->remove($depensePrevue);
        $this->entityManager->flush();
    }

    /**
     * Récupère une dépense prévue par son ID
     */
    public function getDepensePrevueById(int $id, User $user): ?DepensePrevue
    {
        $depensePrevue = $this->entityManager->getRepository(DepensePrevue::class)->find($id);
        if ($depensePrevue && $depensePrevue->getUser() === $user) {
            return $depensePrevue;
        }
        return null;
    }

    /**
     * Liste les dépenses prévues avec filtres et pagination
     */
    public function listDepensesPrevues(User $user, array $filtres, int $page, int $limit): array
    {
        $qb = $this->entityManager->getRepository(DepensePrevue::class)->createQueryBuilder('d');
        $qb->where('d.user = :user')->setParameter('user', $user);

        if (isset($filtres['statut'])) {
            $qb->andWhere('d.statut = :statut')->setParameter('statut', $filtres['statut']);
        }
        if (isset($filtres['typeBudget'])) {
            $qb->andWhere('d.typeBudget = :typeBudget')->setParameter('typeBudget', $filtres['typeBudget']);
        }
        if (isset($filtres['budgetDepasse'])) {
            if ($filtres['budgetDepasse']) {
                $qb->andWhere('d.montantDepense > d.budgetPrevu AND d.budgetPrevu IS NOT NULL');
            } else {
                $qb->andWhere('d.montantDepense <= d.budgetPrevu OR d.budgetPrevu IS NULL');
            }
        }
        if (isset($filtres['enRetard'])) {
            if ($filtres['enRetard']) {
                $qb->andWhere('d.dateFinPrevue < :now AND d.statut != :termine')
                   ->setParameter('now', new \DateTime())
                   ->setParameter('termine', DepensePrevue::STATUT_TERMINE);
            }
        }
        if (isset($filtres['hasBudget'])) {
            if ($filtres['hasBudget']) {
                $qb->andWhere('d.budgetPrevu IS NOT NULL AND d.typeBudget != :inconnu')
                   ->setParameter('inconnu', DepensePrevue::TYPE_BUDGET_INCONNU);
            } else {
                $qb->andWhere('d.budgetPrevu IS NULL OR d.typeBudget = :inconnu')
                   ->setParameter('inconnu', DepensePrevue::TYPE_BUDGET_INCONNU);
            }
        }

        $qb->orderBy('d.dateCreation', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $depensesPrevues = $qb->getQuery()->getResult();

        // Compter le total pour la pagination
        $total = $this->entityManager->getRepository(DepensePrevue::class)->count(['user' => $user]);

        return [
            'depensesPrevues' => $depensesPrevues,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ],
        ];
    }

    /**
     * Met à jour automatiquement les montants et statuts des dépenses prévues
     */
    public function mettreAJourToutesLesDepensesPrevues(User $user): void
    {
        $depensesPrevues = $this->entityManager->getRepository(DepensePrevue::class)
            ->findBy(['user' => $user]);

        foreach ($depensesPrevues as $depensePrevue) {
            $this->mettreAJourDepensePrevueAutomatique($depensePrevue);
        }

        $this->entityManager->flush();
    }

    /**
     * Met à jour automatiquement une dépense prévue spécifique
     */
    public function mettreAJourDepensePrevueAutomatique(DepensePrevue $depensePrevue): void
    {
        // Mettre à jour le montant dépensé
        $depensePrevue->mettreAJourMontantDepense();

        // Mettre à jour le statut
        $depensePrevue->mettreAJourStatut();

        $this->entityManager->persist($depensePrevue);
    }

    /**
     * Démarre une dépense prévue
     */
    public function demarrerDepensePrevue(DepensePrevue $depensePrevue): DepensePrevue
    {
        $depensePrevue->demarrer();
        $this->entityManager->flush();
        return $depensePrevue;
    }

    /**
     * Termine une dépense prévue
     */
    public function terminerDepensePrevue(DepensePrevue $depensePrevue): DepensePrevue
    {
        $depensePrevue->terminer();
        $this->entityManager->flush();
        return $depensePrevue;
    }

    /**
     * Annule une dépense prévue
     */
    public function annulerDepensePrevue(DepensePrevue $depensePrevue): DepensePrevue
    {
        $depensePrevue->annuler();
        $this->entityManager->flush();
        return $depensePrevue;
    }

    /**
     * Calcule les statistiques des dépenses prévues pour un utilisateur
     */
    public function calculerStatistiques(User $user): array
    {
        $repo = $this->entityManager->getRepository(DepensePrevue::class);
        return $repo->getStatistiques($user);
    }

    /**
     * Récupère les dépenses prévues récentes
     */
    public function getDepensesPrevuesRecentes(User $user, int $limit = 5): array
    {
        return $this->entityManager->getRepository(DepensePrevue::class)
            ->createQueryBuilder('d')
            ->where('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dépenses prévues nécessitant une attention
     */
    public function getDepensesPrevuesAttention(User $user): array
    {
        return $this->entityManager->getRepository(DepensePrevue::class)
            ->createQueryBuilder('d')
            ->where('d.user = :user')
            ->andWhere('(d.dateFinPrevue < :now AND d.statut != :termine) OR (d.montantDepense > d.budgetPrevu AND d.budgetPrevu IS NOT NULL AND d.typeBudget != :inconnu)')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->setParameter('termine', DepensePrevue::STATUT_TERMINE)
            ->setParameter('inconnu', DepensePrevue::TYPE_BUDGET_INCONNU)
            ->orderBy('d.dateFinPrevue', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée une dépense prévue simple (sans budget)
     */
    public function creerDepensePrevueSimple(User $user, string $nom, ?string $description = null): DepensePrevue
    {
        return $this->creerDepensePrevue($user, [
            'nom' => $nom,
            'description' => $description,
            'typeBudget' => DepensePrevue::TYPE_BUDGET_INCONNU
        ]);
    }

    /**
     * Crée une dépense prévue avec budget estimé
     */
    public function creerDepensePrevueEstimee(User $user, string $nom, float $budgetEstime, ?string $description = null): DepensePrevue
    {
        return $this->creerDepensePrevue($user, [
            'nom' => $nom,
            'description' => $description,
            'budgetPrevu' => number_format($budgetEstime, 2, '.', ''),
            'typeBudget' => DepensePrevue::TYPE_BUDGET_ESTIME
        ]);
    }

    /**
     * Crée une dépense prévue avec budget certain
     */
    public function creerDepensePrevueCertaine(User $user, string $nom, float $budgetCertain, ?string $description = null): DepensePrevue
    {
        return $this->creerDepensePrevue($user, [
            'nom' => $nom,
            'description' => $description,
            'budgetPrevu' => number_format($budgetCertain, 2, '.', ''),
            'typeBudget' => DepensePrevue::TYPE_BUDGET_CERTAIN
        ]);
    }
}
