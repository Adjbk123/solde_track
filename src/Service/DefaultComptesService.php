<?php

namespace App\Service;

use App\Entity\Compte;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DefaultComptesService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Crée un compte principal par défaut pour un nouvel utilisateur
     */
    public function createDefaultCompteForUser(User $user): Compte
    {
        $compte = new Compte();
        $compte->setUser($user);
        $compte->setNom('Compte Principal');
        $compte->setDescription('Compte principal pour vos transactions quotidiennes');
        $compte->setDevise($user->getDevise());
        $compte->setSoldeInitial('0.00');
        $compte->setSoldeActuel('0.00');
        $compte->setType(Compte::TYPE_COMPTE_PRINCIPAL);
        $compte->setActif(true);

        $this->entityManager->persist($compte);
        $this->entityManager->flush();

        return $compte;
    }

    /**
     * Crée des comptes par défaut pour un nouvel utilisateur
     */
    public function createDefaultComptesForUser(User $user): void
    {
        $devise = $user->getDevise();
        
        $defaultComptes = [
            [
                'nom' => 'Compte Principal',
                'description' => 'Compte principal pour vos transactions quotidiennes',
                'type' => Compte::TYPE_COMPTE_PRINCIPAL,
                'solde_initial' => '0.00'
            ],
            [
                'nom' => 'Épargne',
                'description' => 'Compte d\'épargne pour vos économies',
                'type' => Compte::TYPE_EPARGNE,
                'solde_initial' => '0.00'
            ],
            [
                'nom' => 'Mobile Money',
                'description' => 'Compte Mobile Money (MoMo, Orange Money, etc.)',
                'type' => Compte::TYPE_MOMO,
                'solde_initial' => '0.00'
            ],
            [
                'nom' => 'Espèces',
                'description' => 'Argent liquide en espèces',
                'type' => Compte::TYPE_ESPECES,
                'solde_initial' => '0.00'
            ]
        ];

        foreach ($defaultComptes as $compteData) {
            $compte = new Compte();
            $compte->setUser($user);
            $compte->setNom($compteData['nom']);
            $compte->setDescription($compteData['description']);
            $compte->setDevise($devise);
            $compte->setType($compteData['type']);
            $compte->setSoldeInitial($compteData['solde_initial']);
            $compte->setSoldeActuel($compteData['solde_initial']);
            $compte->setActif(true);

            $this->entityManager->persist($compte);
        }

        $this->entityManager->flush();
    }

    /**
     * Crée un compte avec un solde initial
     */
    public function createCompteWithSolde(User $user, string $nom, string $type, string $soldeInitial, ?string $description = null): Compte
    {
        $compte = new Compte();
        $compte->setUser($user);
        $compte->setNom($nom);
        $compte->setDescription($description ?? "Compte $nom");
        $compte->setDevise($user->getDevise());
        $compte->setType($type);
        $compte->setSoldeInitial($soldeInitial);
        $compte->setSoldeActuel($soldeInitial);
        $compte->setActif(true);

        $this->entityManager->persist($compte);
        $this->entityManager->flush();

        return $compte;
    }

    /**
     * Trouve ou crée le compte principal d'un utilisateur
     */
    public function getOrCreateComptePrincipal(User $user): Compte
    {
        $compteRepo = $this->entityManager->getRepository(Compte::class);
        $comptePrincipal = $compteRepo->findComptePrincipalByUser($user);

        if (!$comptePrincipal) {
            $comptePrincipal = $this->createDefaultCompteForUser($user);
        }

        return $comptePrincipal;
    }

    /**
     * Met à jour le solde d'un compte
     */
    public function updateCompteSolde(Compte $compte): void
    {
        $compte->mettreAJourSolde();
        $this->entityManager->flush();
    }

    /**
     * Transfère de l'argent entre deux comptes
     */
    public function transfererArgent(Compte $compteSource, Compte $compteDestination, string $montant, ?string $description = null): void
    {
        // Vérifier que les comptes appartiennent au même utilisateur
        if ($compteSource->getUser() !== $compteDestination->getUser()) {
            throw new \Exception('Les comptes doivent appartenir au même utilisateur');
        }

        // Vérifier que les comptes ont la même devise
        if ($compteSource->getDevise() !== $compteDestination->getDevise()) {
            throw new \Exception('Les comptes doivent avoir la même devise');
        }

        $montantFloat = (float) $montant;

        // Vérifier que le compte source a suffisamment d'argent
        if ((float) $compteSource->getSoldeActuel() < $montantFloat) {
            throw new \Exception('Solde insuffisant dans le compte source');
        }

        // Mettre à jour les soldes
        $nouveauSoldeSource = (float) $compteSource->getSoldeActuel() - $montantFloat;
        $nouveauSoldeDestination = (float) $compteDestination->getSoldeActuel() + $montantFloat;

        $compteSource->setSoldeActuel(number_format($nouveauSoldeSource, 2, '.', ''));
        $compteDestination->setSoldeActuel(number_format($nouveauSoldeDestination, 2, '.', ''));

        $this->entityManager->flush();
    }

    /**
     * Désactive un compte (soft delete)
     */
    public function desactiverCompte(Compte $compte): void
    {
        $compte->setActif(false);
        $this->entityManager->flush();
    }

    /**
     * Réactive un compte
     */
    public function reactiverCompte(Compte $compte): void
    {
        $compte->setActif(true);
        $this->entityManager->flush();
    }
}
