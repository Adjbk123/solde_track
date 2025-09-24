<?php

namespace App\Service;

use App\Entity\Categorie;
use App\Entity\Mouvement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DebtManagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Détermine le type de catégorie approprié pour un type de mouvement de dette
     */
    public function getCategoryTypeForDebtMovement(string $movementType): string
    {
        return match($movementType) {
            Mouvement::TYPE_DETTE_A_RECEVOIR => Categorie::TYPE_ENTREE,
            Mouvement::TYPE_DETTE_A_PAYER => Categorie::TYPE_SORTIE,
            default => throw new \InvalidArgumentException("Type de mouvement de dette invalide: {$movementType}")
        };
    }

    /**
     * Détermine les catégories appropriées pour un type de mouvement de dette
     */
    public function getSuggestedCategoriesForDebtMovement(string $movementType): array
    {
        return match($movementType) {
            Mouvement::TYPE_DETTE_A_RECEVOIR => [
                'Prêt à recevoir',
                'Créance',
                'Remboursement'
            ],
            Mouvement::TYPE_DETTE_A_PAYER => [
                'Prêt à rembourser',
                'Dette'
            ],
            default => []
        };
    }

    /**
     * Valide qu'une catégorie est compatible avec un type de mouvement de dette
     */
    public function isCategoryCompatibleWithDebtMovement(Categorie $categorie, string $movementType): bool
    {
        $expectedCategoryType = $this->getCategoryTypeForDebtMovement($movementType);
        return $categorie->getType() === $expectedCategoryType;
    }

    /**
     * Trouve ou crée une catégorie appropriée pour un mouvement de dette
     */
    public function findOrCreateCategoryForDebtMovement(User $user, string $movementType, ?string $preferredName = null): Categorie
    {
        $categoryType = $this->getCategoryTypeForDebtMovement($movementType);
        $suggestedNames = $this->getSuggestedCategoriesForDebtMovement($movementType);
        
        // Si un nom préféré est fourni, l'utiliser
        $searchName = $preferredName ?? $suggestedNames[0];
        
        // Chercher une catégorie existante
        $existingCategory = $this->entityManager->getRepository(Categorie::class)
            ->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.type = :type')
            ->andWhere('c.nom = :nom')
            ->setParameter('user', $user)
            ->setParameter('type', $categoryType)
            ->setParameter('nom', $searchName)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingCategory) {
            return $existingCategory;
        }

        // Créer une nouvelle catégorie
        $categorie = new Categorie();
        $categorie->setUser($user);
        $categorie->setNom($searchName);
        $categorie->setType($categoryType);

        $this->entityManager->persist($categorie);
        $this->entityManager->flush();

        return $categorie;
    }

    /**
     * Obtient toutes les catégories compatibles avec un type de mouvement de dette
     */
    public function getCompatibleCategoriesForDebtMovement(User $user, string $movementType): array
    {
        $categoryType = $this->getCategoryTypeForDebtMovement($movementType);
        
        return $this->entityManager->getRepository(Categorie::class)
            ->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $categoryType)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le solde des dettes pour un utilisateur
     */
    public function calculateDebtBalance(User $user): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        $dettesAPayer = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_PAYER);
        $dettesARecevoir = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_DETTE_A_RECEVOIR);
        
        return [
            'dettes_a_payer' => $dettesAPayer,
            'dettes_a_recevoir' => $dettesARecevoir,
            'solde_dettes' => $dettesARecevoir - $dettesAPayer,
            'net_positive' => $dettesARecevoir > $dettesAPayer
        ];
    }
}
