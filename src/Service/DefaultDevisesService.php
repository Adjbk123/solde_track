<?php

namespace App\Service;

use App\Entity\Devise;
use Doctrine\ORM\EntityManagerInterface;

class DefaultDevisesService
{
    private const DEFAULT_DEVISES = [
        ['code' => 'XOF', 'nom' => 'Franc CFA (Afrique de l\'Ouest)'],
        ['code' => 'XAF', 'nom' => 'Franc CFA (Afrique Centrale)'],
        ['code' => 'EUR', 'nom' => 'Euro'],
        ['code' => 'USD', 'nom' => 'Dollar Américain'],
        ['code' => 'GBP', 'nom' => 'Livre Sterling'],
        ['code' => 'JPY', 'nom' => 'Yen Japonais'],
        ['code' => 'CAD', 'nom' => 'Dollar Canadien'],
        ['code' => 'AUD', 'nom' => 'Dollar Australien'],
        ['code' => 'CHF', 'nom' => 'Franc Suisse'],
        ['code' => 'CNY', 'nom' => 'Yuan Chinois'],
        ['code' => 'INR', 'nom' => 'Roupie Indienne'],
        ['code' => 'BRL', 'nom' => 'Real Brésilien'],
        ['code' => 'RUB', 'nom' => 'Rouble Russe'],
        ['code' => 'ZAR', 'nom' => 'Rand Sud-Africain'],
        ['code' => 'NGN', 'nom' => 'Naira Nigérian'],
        ['code' => 'EGP', 'nom' => 'Livre Égyptienne'],
        ['code' => 'MAD', 'nom' => 'Dirham Marocain'],
        ['code' => 'TND', 'nom' => 'Dinar Tunisien'],
        ['code' => 'DZD', 'nom' => 'Dinar Algérien'],
        ['code' => 'GHS', 'nom' => 'Cedi Ghanéen'],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Crée les devises par défaut dans la base de données
     */
    public function createDefaultDevises(): void
    {
        foreach (self::DEFAULT_DEVISES as $deviseData) {
            // Vérifier si la devise existe déjà
            $existingDevise = $this->entityManager->getRepository(Devise::class)
                ->findByCode($deviseData['code']);
            
            if (!$existingDevise) {
                $devise = new Devise();
                $devise->setCode($deviseData['code']);
                $devise->setNom($deviseData['nom']);
                
                $this->entityManager->persist($devise);
            }
        }
        
        $this->entityManager->flush();
    }

    /**
     * Retourne la liste des devises par défaut
     */
    public function getDefaultDevises(): array
    {
        return self::DEFAULT_DEVISES;
    }

    /**
     * Vérifie si les devises par défaut existent dans la base
     */
    public function devisesExist(): bool
    {
        $count = $this->entityManager->getRepository(Devise::class)
            ->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Retourne la devise par défaut (XOF - Franc CFA)
     */
    public function getDefaultDevise(): ?Devise
    {
        return $this->entityManager->getRepository(Devise::class)
            ->findByCode('XOF');
    }
}
