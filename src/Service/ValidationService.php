<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Categorie;
use App\Entity\Contact;
use App\Entity\Compte;
use App\Entity\Projet;
use Doctrine\ORM\EntityManagerInterface;

class ValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Valide les données de base d'un mouvement
     */
    public function validerDonneesMouvement(array $donnees): array
    {
        $erreurs = [];

        // Validation du montant
        if (!isset($donnees['montantTotal'])) {
            $erreurs[] = 'Le montant total est requis';
        } elseif (!is_numeric($donnees['montantTotal']) || (float) $donnees['montantTotal'] <= 0) {
            $erreurs[] = 'Le montant total doit être un nombre positif';
        }

        // Validation de la catégorie
        if (!isset($donnees['categorie_id'])) {
            $erreurs[] = 'La catégorie est requise';
        } elseif (!is_numeric($donnees['categorie_id'])) {
            $erreurs[] = 'L\'ID de la catégorie doit être un nombre';
        }

        // Validation de la date
        if (isset($donnees['date'])) {
            if (!$this->estDateValide($donnees['date'])) {
                $erreurs[] = 'Le format de date est invalide (utilisez YYYY-MM-DD)';
            }
        }

        // Validation des IDs optionnels
        if (isset($donnees['contact_id']) && !is_numeric($donnees['contact_id'])) {
            $erreurs[] = 'L\'ID du contact doit être un nombre';
        }

        if (isset($donnees['compte_id']) && !is_numeric($donnees['compte_id'])) {
            $erreurs[] = 'L\'ID du compte doit être un nombre';
        }

        if (isset($donnees['projet_id']) && !is_numeric($donnees['projet_id'])) {
            $erreurs[] = 'L\'ID du projet doit être un nombre';
        }

        return $erreurs;
    }

    /**
     * Valide les données spécifiques d'une dépense
     */
    public function validerDonneesDepense(array $donnees): array
    {
        $erreurs = [];

        // Validation de la méthode de paiement
        if (isset($donnees['methodePaiement'])) {
            $methodesValides = ['cash', 'momo', 'virement', 'autre'];
            if (!in_array($donnees['methodePaiement'], $methodesValides)) {
                $erreurs[] = 'Méthode de paiement invalide';
            }
        }

        return $erreurs;
    }

    /**
     * Valide les données spécifiques d'une entrée
     */
    public function validerDonneesEntree(array $donnees): array
    {
        $erreurs = [];

        // Validation de la méthode
        if (isset($donnees['methode'])) {
            $methodesValides = ['cash', 'momo', 'virement', 'autre'];
            if (!in_array($donnees['methode'], $methodesValides)) {
                $erreurs[] = 'Méthode invalide';
            }
        }

        // Validation de la source
        if (isset($donnees['source']) && empty(trim($donnees['source']))) {
            $erreurs[] = 'La source ne peut pas être vide';
        }

        // Validation du statut
        if (isset($donnees['statut'])) {
            $statutsValides = array_keys(\App\Entity\StatutsMouvement::getStatutsForType('entree'));
            if (!in_array($donnees['statut'], $statutsValides)) {
                $erreurs[] = 'Statut invalide pour une entrée';
            }
        }

        return $erreurs;
    }

    /**
     * Valide les données spécifiques d'un don
     */
    public function validerDonneesDon(array $donnees): array
    {
        $erreurs = [];

        // Validation de l'occasion
        if (isset($donnees['occasion']) && empty(trim($donnees['occasion']))) {
            $erreurs[] = 'L\'occasion ne peut pas être vide';
        }

        // Validation du statut
        if (isset($donnees['statut'])) {
            $statutsValides = array_keys(\App\Entity\StatutsMouvement::getStatutsForType('don'));
            if (!in_array($donnees['statut'], $statutsValides)) {
                $erreurs[] = 'Statut invalide pour un don';
            }
        }

        return $erreurs;
    }

    /**
     * Vérifie qu'une catégorie existe et appartient à l'utilisateur
     */
    public function validerCategorie(User $user, int $categorieId): ?Categorie
    {
        $categorie = $this->entityManager->getRepository(Categorie::class)->find($categorieId);
        
        if (!$categorie) {
            throw new \InvalidArgumentException('Catégorie non trouvée');
        }
        
        if ($categorie->getUser() !== $user) {
            throw new \InvalidArgumentException('Catégorie non autorisée');
        }
        
        return $categorie;
    }

    /**
     * Vérifie qu'un contact existe et appartient à l'utilisateur
     */
    public function validerContact(User $user, ?int $contactId): ?Contact
    {
        if (!$contactId) {
            return null;
        }

        $contact = $this->entityManager->getRepository(Contact::class)->find($contactId);
        
        if (!$contact) {
            throw new \InvalidArgumentException('Contact non trouvé');
        }
        
        if ($contact->getUser() !== $user) {
            throw new \InvalidArgumentException('Contact non autorisé');
        }
        
        return $contact;
    }

    /**
     * Vérifie qu'un compte existe et appartient à l'utilisateur
     */
    public function validerCompte(User $user, ?int $compteId): ?Compte
    {
        if (!$compteId) {
            return null;
        }

        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        
        if (!$compte) {
            throw new \InvalidArgumentException('Compte non trouvé');
        }
        
        if ($compte->getUser() !== $user) {
            throw new \InvalidArgumentException('Compte non autorisé');
        }
        
        return $compte;
    }

    /**
     * Vérifie qu'un projet existe et appartient à l'utilisateur
     */
    public function validerProjet(User $user, ?int $projetId): ?Projet
    {
        if (!$projetId) {
            return null;
        }

        $projet = $this->entityManager->getRepository(Projet::class)->find($projetId);
        
        if (!$projet) {
            throw new \InvalidArgumentException('Projet non trouvé');
        }
        
        if ($projet->getUser() !== $user) {
            throw new \InvalidArgumentException('Projet non autorisé');
        }
        
        return $projet;
    }

    /**
     * Valide qu'un type de mouvement est valide
     */
    public function validerTypeMouvement(string $type): bool
    {
        $typesValides = ['sortie', 'entree', 'don'];
        return in_array($type, $typesValides);
    }

    /**
     * Valide qu'une date est au bon format
     */
    private function estDateValide(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Nettoie et valide les données d'entrée
     */
    public function nettoyerDonnees(array $donnees): array
    {
        $donneesNettoyees = [];

        // Nettoyer les champs de base
        if (isset($donnees['montantTotal'])) {
            $donneesNettoyees['montantTotal'] = trim($donnees['montantTotal']);
        }

        if (isset($donnees['description'])) {
            $donneesNettoyees['description'] = trim($donnees['description']);
        }

        if (isset($donnees['date'])) {
            $donneesNettoyees['date'] = trim($donnees['date']);
        }

        // Nettoyer les IDs
        if (isset($donnees['categorie_id'])) {
            $donneesNettoyees['categorie_id'] = (int) $donnees['categorie_id'];
        }

        if (isset($donnees['contact_id'])) {
            $donneesNettoyees['contact_id'] = (int) $donnees['contact_id'];
        }

        if (isset($donnees['compte_id'])) {
            $donneesNettoyees['compte_id'] = (int) $donnees['compte_id'];
        }

        if (isset($donnees['projet_id'])) {
            $donneesNettoyees['projet_id'] = (int) $donnees['projet_id'];
        }

        // Nettoyer les champs spécifiques
        if (isset($donnees['lieu'])) {
            $donneesNettoyees['lieu'] = trim($donnees['lieu']);
        }

        if (isset($donnees['source'])) {
            $donneesNettoyees['source'] = trim($donnees['source']);
        }

        if (isset($donnees['occasion'])) {
            $donneesNettoyees['occasion'] = trim($donnees['occasion']);
        }

        if (isset($donnees['methodePaiement'])) {
            $donneesNettoyees['methodePaiement'] = trim($donnees['methodePaiement']);
        }

        if (isset($donnees['methode'])) {
            $donneesNettoyees['methode'] = trim($donnees['methode']);
        }

        if (isset($donnees['recu'])) {
            $donneesNettoyees['recu'] = trim($donnees['recu']);
        }

        return $donneesNettoyees;
    }
}
