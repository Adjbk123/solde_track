<?php

namespace App\Service;

use App\Entity\Transfert;
use App\Entity\User;
use App\Entity\Compte;
use App\Entity\Devise;
use Doctrine\ORM\EntityManagerInterface;

class TransfertService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Crée et exécute un transfert entre deux comptes
     */
    public function creerTransfert(
        User $user,
        Compte $compteSource,
        Compte $compteDestination,
        string $montant,
        ?string $note = null
    ): Transfert {
        // Vérifier que les comptes appartiennent à l'utilisateur
        if ($compteSource->getUser() !== $user || $compteDestination->getUser() !== $user) {
            throw new \Exception('Les comptes doivent appartenir à l\'utilisateur');
        }

        // Vérifier que les comptes sont différents
        if ($compteSource === $compteDestination) {
            throw new \Exception('Le compte source et destination doivent être différents');
        }

        // Vérifier que les comptes ont la même devise
        if ($compteSource->getDevise() !== $compteDestination->getDevise()) {
            throw new \Exception('Les comptes doivent avoir la même devise');
        }

        // Vérifier que le montant est positif
        if ((float) $montant <= 0) {
            throw new \Exception('Le montant doit être positif');
        }

        // Vérifier que le compte source a suffisamment d'argent
        if ((float) $compteSource->getSoldeActuel() < (float) $montant) {
            throw new \Exception('Solde insuffisant dans le compte source');
        }

        // Créer le transfert
        $transfert = new Transfert();
        $transfert->setUser($user);
        $transfert->setCompteSource($compteSource);
        $transfert->setCompteDestination($compteDestination);
        $transfert->setMontant($montant);
        $transfert->setDevise($compteSource->getDevise());
        $transfert->setNote($note);

        // Exécuter le transfert
        $transfert->executer();

        // Sauvegarder
        $this->entityManager->persist($transfert);
        $this->entityManager->flush();

        return $transfert;
    }

    /**
     * Annule un transfert
     */
    public function annulerTransfert(Transfert $transfert): void
    {
        $transfert->annuler();
        $this->entityManager->flush();
    }

    /**
     * Trouve tous les transferts d'un utilisateur
     */
    public function getTransfertsByUser(User $user): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findByUser($user);
    }

    /**
     * Trouve les transferts d'un compte
     */
    public function getTransfertsByCompte(Compte $compte): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findByCompte($compte);
    }

    /**
     * Trouve les transferts récents d'un utilisateur
     */
    public function getTransfertsRecents(User $user, int $limit = 10): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findRecentsByUser($user, $limit);
    }

    /**
     * Trouve les transferts dans une période
     */
    public function getTransfertsByDateRange(User $user, \DateTime $debut, \DateTime $fin): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findByUserAndDateRange($user, $debut, $fin);
    }

    /**
     * Calcule les statistiques des transferts d'un utilisateur
     */
    public function getStatistiquesTransferts(User $user): array
    {
        $transfertRepo = $this->entityManager->getRepository(Transfert::class);
        
        $statistiques = $transfertRepo->getStatistiquesParDevise($user);
        $montantTotal = $transfertRepo->getMontantTotalByUser($user);
        $transfertsRecents = $transfertRepo->findRecentsByUser($user, 5);

        return [
            'statistiques_par_devise' => $statistiques,
            'montant_total' => $montantTotal,
            'transferts_recents' => $transfertsRecents,
            'nombre_total' => count($this->getTransfertsByUser($user))
        ];
    }

    /**
     * Trouve les transferts entre deux comptes
     */
    public function getTransfertsEntreComptes(Compte $compte1, Compte $compte2): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findByComptes($compte1, $compte2);
    }

    /**
     * Calcule le solde net des transferts d'un compte
     */
    public function getSoldeNetTransferts(Compte $compte): float
    {
        return $this->entityManager->getRepository(Transfert::class)->getSoldeNetTransferts($compte);
    }

    /**
     * Trouve les transferts d'un mois donné
     */
    public function getTransfertsByMois(User $user, int $annee, int $mois): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findByMois($user, $annee, $mois);
    }

    /**
     * Trouve les transferts par montant minimum
     */
    public function getTransfertsByMontantMinimum(User $user, float $montantMinimum): array
    {
        return $this->entityManager->getRepository(Transfert::class)->findByMontantMinimum($user, $montantMinimum);
    }

    /**
     * Valide qu'un transfert est possible
     */
    public function validerTransfert(Compte $compteSource, Compte $compteDestination, string $montant): array
    {
        $erreurs = [];

        // Vérifier que les comptes sont différents
        if ($compteSource === $compteDestination) {
            $erreurs[] = 'Le compte source et destination doivent être différents';
        }

        // Vérifier que les comptes ont la même devise
        if ($compteSource->getDevise() !== $compteDestination->getDevise()) {
            $erreurs[] = 'Les comptes doivent avoir la même devise';
        }

        // Vérifier que le montant est positif
        if ((float) $montant <= 0) {
            $erreurs[] = 'Le montant doit être positif';
        }

        // Vérifier que le compte source a suffisamment d'argent
        if ((float) $compteSource->getSoldeActuel() < (float) $montant) {
            $erreurs[] = 'Solde insuffisant dans le compte source';
        }

        return $erreurs;
    }

    /**
     * Simule un transfert sans l'exécuter
     */
    public function simulerTransfert(Compte $compteSource, Compte $compteDestination, string $montant): array
    {
        $erreurs = $this->validerTransfert($compteSource, $compteDestination, $montant);
        
        if (empty($erreurs)) {
            $montantFloat = (float) $montant;
            $nouveauSoldeSource = (float) $compteSource->getSoldeActuel() - $montantFloat;
            $nouveauSoldeDestination = (float) $compteDestination->getSoldeActuel() + $montantFloat;

            return [
                'valide' => true,
                'nouveau_solde_source' => number_format($nouveauSoldeSource, 2, '.', ''),
                'nouveau_solde_destination' => number_format($nouveauSoldeDestination, 2, '.', ''),
                'montant' => $montant,
                'devise' => $compteSource->getDevise()->getCode()
            ];
        }

        return [
            'valide' => false,
            'erreurs' => $erreurs
        ];
    }
}
