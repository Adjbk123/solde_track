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
            Mouvement::TYPE_PRET => Categorie::TYPE_ENTREE,
            Mouvement::TYPE_EMPRUNT => Categorie::TYPE_SORTIE,
            Mouvement::TYPE_CREANCE => Categorie::TYPE_ENTREE,
            default => throw new \InvalidArgumentException("Type de mouvement de dette invalide: {$movementType}")
        };
    }

    /**
     * Détermine les catégories appropriées pour un type de mouvement de dette
     */
    public function getSuggestedCategoriesForDebtMovement(string $movementType): array
    {
        return match($movementType) {
            Mouvement::TYPE_PRET => [
                'Prêt à recevoir',
                'Créance',
                'Remboursement'
            ],
            Mouvement::TYPE_EMPRUNT => [
                'Prêt à rembourser',
                'Dette'
            ],
            Mouvement::TYPE_CREANCE => [
                'Créance commerciale',
                'Facture impayée',
                'Autre créance'
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
    public function calculateDebtBalance(User $user, ?\DateTime $debut = null, ?\DateTime $fin = null): array
    {
        $mouvementRepo = $this->entityManager->getRepository(Mouvement::class);
        
        // Si aucune période n'est spécifiée, prendre toutes les dettes
        if ($debut === null) {
            $debut = new \DateTime('1900-01-01');
        }
        if ($fin === null) {
            $fin = new \DateTime();
        }
        
        $emprunts = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_EMPRUNT, $debut, $fin);
        $prets = $mouvementRepo->getTotalByType($user, Mouvement::TYPE_PRET, $debut, $fin);
        
        return [
            'emprunts' => $emprunts,
            'prets' => $prets,
            'solde_dettes' => $prets - $emprunts,
            'net_positive' => $prets > $emprunts
        ];
    }
}
