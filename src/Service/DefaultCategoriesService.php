<?php

namespace App\Service;

use App\Entity\Categorie;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DefaultCategoriesService
{
    private const DEFAULT_CATEGORIES = [
        // Dépenses
        ['nom' => 'Alimentation', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Transport', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Habits', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Santé', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Éducation', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Loisirs', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Logement', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Électricité', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Eau', 'type' => Categorie::TYPE_DEPENSE],
        ['nom' => 'Internet/Téléphone', 'type' => Categorie::TYPE_DEPENSE],
        
        // Entrées
        ['nom' => 'Salaire', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Vente', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Remboursement', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Prime', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Investissement', 'type' => Categorie::TYPE_ENTREE],
        
        // Dettes
        ['nom' => 'Emprunt à rembourser', 'type' => Categorie::TYPE_DETTE],
        ['nom' => 'Emprunt à recevoir', 'type' => Categorie::TYPE_DETTE],
        ['nom' => 'Crédit', 'type' => Categorie::TYPE_DETTE],
        
        // Dons
        ['nom' => 'Cadeau', 'type' => Categorie::TYPE_DON],
        ['nom' => 'Soutien famille', 'type' => Categorie::TYPE_DON],
        ['nom' => 'Charité', 'type' => Categorie::TYPE_DON],
        ['nom' => 'Aide', 'type' => Categorie::TYPE_DON],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Crée les catégories par défaut pour un nouvel utilisateur
     */
    public function createDefaultCategoriesForUser(User $user): void
    {
        foreach (self::DEFAULT_CATEGORIES as $categoryData) {
            $categorie = new Categorie();
            $categorie->setUser($user);
            $categorie->setNom($categoryData['nom']);
            $categorie->setType($categoryData['type']);
            
            $this->entityManager->persist($categorie);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Retourne la liste des catégories par défaut
     */
    public function getDefaultCategories(): array
    {
        return self::DEFAULT_CATEGORIES;
    }

    /**
     * Vérifie si un utilisateur a déjà des catégories
     */
    public function userHasCategories(User $user): bool
    {
        $count = $this->entityManager->getRepository(Categorie::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
