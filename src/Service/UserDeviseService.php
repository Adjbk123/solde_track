<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Devise;
use Doctrine\ORM\EntityManagerInterface;

class UserDeviseService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère la devise de l'utilisateur
     */
    public function getUserDevise(User $user): ?Devise
    {
        return $user->getDevise();
    }

    /**
     * Récupère le code de la devise de l'utilisateur
     */
    public function getUserDeviseCode(User $user): ?string
    {
        return $user->getDevise()?->getCode();
    }

    /**
     * Récupère le nom de la devise de l'utilisateur
     */
    public function getUserDeviseName(User $user): ?string
    {
        return $user->getDevise()?->getNom();
    }

    /**
     * Change la devise de l'utilisateur
     */
    public function changeUserDevise(User $user, Devise $devise): void
    {
        $user->setDevise($devise);
        $this->entityManager->flush();
    }

    /**
     * Formate un montant avec la devise de l'utilisateur
     */
    public function formatAmount(User $user, float $amount): string
    {
        $deviseCode = $this->getUserDeviseCode($user);
        
        // Formatage selon la devise
        switch ($deviseCode) {
            case 'XOF':
            case 'XAF':
                return number_format($amount, 0, ',', ' ') . ' ' . $deviseCode;
            case 'EUR':
                return number_format($amount, 2, ',', ' ') . ' €';
            case 'USD':
                return '$' . number_format($amount, 2, '.', ',');
            case 'GBP':
                return '£' . number_format($amount, 2, '.', ',');
            default:
                return number_format($amount, 2, '.', ',') . ' ' . $deviseCode;
        }
    }

    /**
     * Convertit un montant d'une devise à une autre
     * Note: Cette fonction nécessiterait une API de conversion en temps réel
     */
    public function convertAmount(float $amount, string $fromDevise, string $toDevise): float
    {
        // Pour l'instant, on retourne le montant tel quel
        // Dans une vraie application, vous utiliseriez une API comme fixer.io ou exchangerate-api.com
        return $amount;
    }

    /**
     * Récupère les devises les plus populaires pour l'inscription
     */
    public function getPopularDevises(): array
    {
        $popularCodes = ['XOF', 'XAF', 'EUR', 'USD', 'GBP'];
        
        $devises = [];
        foreach ($popularCodes as $code) {
            $devise = $this->entityManager->getRepository(Devise::class)->findByCode($code);
            if ($devise) {
                $devises[] = [
                    'id' => $devise->getId(),
                    'code' => $devise->getCode(),
                    'nom' => $devise->getNom()
                ];
            }
        }
        
        return $devises;
    }

    /**
     * Récupère toutes les devises disponibles
     */
    public function getAllDevises(): array
    {
        $devises = $this->entityManager->getRepository(Devise::class)->findAll();
        
        $data = [];
        foreach ($devises as $devise) {
            $data[] = [
                'id' => $devise->getId(),
                'code' => $devise->getCode(),
                'nom' => $devise->getNom()
            ];
        }
        
        return $data;
    }
}
