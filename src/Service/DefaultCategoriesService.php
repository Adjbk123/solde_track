<?php

namespace App\Service;

use App\Entity\Categorie;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DefaultCategoriesService
{
    private const DEFAULT_CATEGORIES = [
        // ENTREES
        ['nom' => 'Salaire', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Vente', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Remboursement', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Prime', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Investissement', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Don reçu', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Prêt à recevoir', 'type' => Categorie::TYPE_ENTREE],
        ['nom' => 'Créance', 'type' => Categorie::TYPE_ENTREE],
        
        // SORTIES
        ['nom' => 'Alimentation', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Transport', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Habits', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Santé', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Éducation', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Loisirs', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Logement', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Électricité', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Eau', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Internet/Téléphone', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Don donné', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Prêt à rembourser', 'type' => Categorie::TYPE_SORTIE],
        ['nom' => 'Dette', 'type' => Categorie::TYPE_SORTIE],
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
