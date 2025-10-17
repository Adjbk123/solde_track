<?php

namespace App\Service;

use App\Entity\Mouvement;
use App\Entity\Depense;
use App\Entity\Entree;
use App\Entity\Don;
use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Compte;
use App\Entity\Categorie;
use App\Entity\DepensePrevue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MouvementUnifieService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private ValidationService $validationService
    ) {}

    /**
     * Crée un mouvement selon son type
     */
    public function creerMouvement(User $user, string $type, array $donnees): Mouvement
    {
        // Validation des données de base
        $this->validerDonneesMouvement($donnees);

        // Récupération des entités liées
        $categorie = $this->recupererCategorie($user, $donnees['categorie_id']);
        $contact = $this->recupererContact($user, $donnees['contact_id'] ?? null);
        $compte = $this->recupererCompte($user, $donnees['compte_id'] ?? null);
        $depensePrevue = $this->recupererDepensePrevue($user, $donnees['depense_prevue_id'] ?? null);

        // Création du mouvement selon le type
        $mouvement = match($type) {
            'sortie' => $this->creerDepense($user, $donnees, $categorie, $contact, $compte, $depensePrevue),
            'entree' => $this->creerEntree($user, $donnees, $categorie, $contact, $compte, $depensePrevue),
            'don' => $this->creerDon($user, $donnees, $categorie, $contact, $compte, $depensePrevue),
            default => throw new \InvalidArgumentException("Type de mouvement invalide: {$type}")
        };

        // Validation spécifique selon le type
        $this->validerMouvementSpecifique($mouvement, $donnees);

        // Validation de l'entité
        $this->validerEntite($mouvement);

        // Persistance
        $this->entityManager->persist($mouvement);
        $this->mettreAJourSoldeCompte($mouvement);
        
        // Mettre à jour la dépense prévue si associée
        if ($mouvement->getDepensePrevue()) {
            $this->mettreAJourDepensePrevue($mouvement->getDepensePrevue());
        }
        
        $this->entityManager->flush();

        return $mouvement;
    }

    /**
     * Met à jour un mouvement existant
     */
    public function mettreAJourMouvement(Mouvement $mouvement, array $donnees): Mouvement
    {
        // Mise à jour des champs modifiables
        if (isset($donnees['montantTotal'])) {
            $mouvement->setMontantTotal($donnees['montantTotal']);
        }
        if (isset($donnees['description'])) {
            $mouvement->setDescription($donnees['description']);
        }
        if (isset($donnees['date'])) {
            $mouvement->setDate(new \DateTime($donnees['date']));
        }

        // Mise à jour spécifique selon le type
        if ($mouvement instanceof Depense) {
            $this->mettreAJourDepense($mouvement, $donnees);
        } elseif ($mouvement instanceof Entree) {
            $this->mettreAJourEntree($mouvement, $donnees);
        } elseif ($mouvement instanceof Don) {
            $this->mettreAJourDon($mouvement, $donnees);
        }

        // Validation
        $this->validerEntite($mouvement);

        // Mettre à jour la dépense prévue si associée
        if ($mouvement->getDepensePrevue()) {
            $this->mettreAJourDepensePrevue($mouvement->getDepensePrevue());
        }

        // Persistance
        $this->entityManager->flush();

        return $mouvement;
    }

    /**
     * Supprime un mouvement
     */
    public function supprimerMouvement(Mouvement $mouvement): void
    {
        // Vérifier s'il y a des paiements associés (pour les dettes)
        if ($mouvement instanceof \App\Entity\Dette && $mouvement->getPaiements()->count() > 0) {
            throw new \InvalidArgumentException('Impossible de supprimer une dette avec des paiements associés');
        }

        // Mettre à jour la dépense prévue avant suppression
        if ($mouvement->getDepensePrevue()) {
            $this->mettreAJourDepensePrevue($mouvement->getDepensePrevue());
        }

        $this->entityManager->remove($mouvement);
        $this->entityManager->flush();
    }

    // Méthodes privées pour la création des mouvements

    private function creerDepense(User $user, array $donnees, Categorie $categorie, ?Contact $contact, ?Compte $compte, ?DepensePrevue $depensePrevue): Depense
    {
        $depense = new Depense();
        $depense->setUser($user);
        $depense->setMontantTotal($donnees['montantTotal']);
        $depense->setCategorie($categorie);
        $depense->setDescription($donnees['description'] ?? null);
        $depense->setLieu($donnees['lieu'] ?? null);
        $depense->setMethodePaiement($donnees['methodePaiement'] ?? null);
        $depense->setRecu($donnees['recu'] ?? null);
        $depense->setContact($contact);
        $depense->setCompte($compte);
        $depense->setDepensePrevue($depensePrevue);

        if (isset($donnees['date'])) {
            $depense->setDate(new \DateTime($donnees['date']));
        }

        return $depense;
    }

    private function creerEntree(User $user, array $donnees, Categorie $categorie, ?Contact $contact, ?Compte $compte, ?DepensePrevue $depensePrevue): Entree
    {
        $entree = new Entree();
        $entree->setUser($user);
        $entree->setMontantTotal($donnees['montantTotal']);
        $entree->setCategorie($categorie);
        $entree->setDescription($donnees['description'] ?? null);
        $entree->setSource($donnees['source'] ?? null);
        $entree->setMethode($donnees['methode'] ?? null);
        $entree->setContact($contact);
        $entree->setCompte($compte);
        $entree->setDepensePrevue($depensePrevue);

        if (isset($donnees['date'])) {
            $entree->setDate(new \DateTime($donnees['date']));
        }

        // Gestion du statut pour les entrées
        if (isset($donnees['statut'])) {
            $entree->setStatut($donnees['statut']);
        } else {
            // Statut par défaut pour les entrées : confirmé = payé
            $entree->setStatut('paye');
        }

        return $entree;
    }

    private function creerDon(User $user, array $donnees, Categorie $categorie, ?Contact $contact, ?Compte $compte, ?DepensePrevue $depensePrevue): Don
    {
        $don = new Don();
        $don->setUser($user);
        $don->setMontantTotal($donnees['montantTotal']);
        $don->setCategorie($categorie);
        $don->setDescription($donnees['description'] ?? null);
        $don->setOccasion($donnees['occasion'] ?? null);
        $don->setContact($contact);
        $don->setCompte($compte);
        $don->setDepensePrevue($depensePrevue);

        if (isset($donnees['date'])) {
            $don->setDate(new \DateTime($donnees['date']));
        }

        // Gestion du statut pour les dons
        if (isset($donnees['statut'])) {
            $don->setStatut($donnees['statut']);
        } else {
            // Statut par défaut pour les dons : effectué = payé
            $don->setStatut('paye');
        }

        return $don;
    }

    // Méthodes privées pour la mise à jour des mouvements

    private function mettreAJourDepense(Depense $depense, array $donnees): void
    {
        if (isset($donnees['lieu'])) {
            $depense->setLieu($donnees['lieu']);
        }
        if (isset($donnees['methodePaiement'])) {
            $depense->setMethodePaiement($donnees['methodePaiement']);
        }
        if (isset($donnees['recu'])) {
            $depense->setRecu($donnees['recu']);
        }
    }

    private function mettreAJourEntree(Entree $entree, array $donnees): void
    {
        if (isset($donnees['source'])) {
            $entree->setSource($donnees['source']);
        }
        if (isset($donnees['methode'])) {
            $entree->setMethode($donnees['methode']);
        }
    }

    private function mettreAJourDon(Don $don, array $donnees): void
    {
        if (isset($donnees['occasion'])) {
            $don->setOccasion($donnees['occasion']);
        }
    }

    // Méthodes utilitaires

    private function validerDonneesMouvement(array $donnees): void
    {
        if (!isset($donnees['montantTotal']) || !isset($donnees['categorie_id'])) {
            throw new \InvalidArgumentException('Données manquantes: montantTotal et categorie_id sont requis');
        }

        if ((float) $donnees['montantTotal'] <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif');
        }
    }

    private function validerMouvementSpecifique(Mouvement $mouvement, array $donnees): void
    {
        $erreurs = [];

        if ($mouvement instanceof \App\Entity\Depense) {
            $erreurs = $this->validationService->validerDonneesDepense($donnees);
        } elseif ($mouvement instanceof \App\Entity\Entree) {
            $erreurs = $this->validationService->validerDonneesEntree($donnees);
        } elseif ($mouvement instanceof \App\Entity\Don) {
            $erreurs = $this->validationService->validerDonneesDon($donnees);
        }

        if (!empty($erreurs)) {
            throw new \InvalidArgumentException('Erreurs de validation: ' . implode(', ', $erreurs));
        }
    }

    private function validerEntite($entite): void
    {
        $erreurs = $this->validator->validate($entite);
        if (count($erreurs) > 0) {
            $messages = [];
            foreach ($erreurs as $erreur) {
                $messages[] = $erreur->getMessage();
            }
            throw new \InvalidArgumentException('Erreurs de validation: ' . implode(', ', $messages));
        }
    }

    private function recupererCategorie(User $user, int $categorieId): Categorie
    {
        $categorie = $this->entityManager->getRepository(Categorie::class)->find($categorieId);
        if (!$categorie || $categorie->getUser() !== $user) {
            throw new \InvalidArgumentException('Catégorie non trouvée ou non autorisée');
        }
        return $categorie;
    }

    private function recupererContact(User $user, ?int $contactId): ?Contact
    {
        if (!$contactId) {
            return null;
        }

        $contact = $this->entityManager->getRepository(Contact::class)->find($contactId);
        if (!$contact || $contact->getUser() !== $user) {
            throw new \InvalidArgumentException('Contact non trouvé ou non autorisé');
        }
        return $contact;
    }

    private function recupererCompte(User $user, ?int $compteId): ?Compte
    {
        if (!$compteId) {
            return null;
        }

        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        if (!$compte || $compte->getUser() !== $user) {
            throw new \InvalidArgumentException('Compte non trouvé ou non autorisé');
        }
        return $compte;
    }

    private function recupererDepensePrevue(User $user, ?int $depensePrevueId): ?DepensePrevue
    {
        if (!$depensePrevueId) {
            return null;
        }

        $depensePrevue = $this->entityManager->getRepository(DepensePrevue::class)->find($depensePrevueId);
        if (!$depensePrevue || $depensePrevue->getUser() !== $user) {
            throw new \InvalidArgumentException('Dépense prévue non trouvée ou non autorisée');
        }
        return $depensePrevue;
    }

    private function mettreAJourSoldeCompte(Mouvement $mouvement): void
    {
        if (!$mouvement->getCompte()) {
            return; // Pas de compte associé
        }

        $compte = $mouvement->getCompte();
        $montant = (float) $mouvement->getMontantTotal();
        $soldeActuel = (float) $compte->getSoldeActuel();

        // Calculer le nouveau solde selon le type de mouvement
        switch ($mouvement->getType()) {
            case 'entree':
                // Les entrées augmentent le solde
                $nouveauSolde = $soldeActuel + $montant;
                $mouvement->setMontantEffectif($mouvement->getMontantTotal());
                break;
            
            case 'sortie':
            case 'don':
                // Les dépenses et dons diminuent le solde
                $nouveauSolde = $soldeActuel - $montant;
                $mouvement->setMontantEffectif($mouvement->getMontantTotal());
                break;
            
            default:
                return; // Type non reconnu
        }

        // Mettre à jour le solde du compte
        $compte->setSoldeActuel(number_format($nouveauSolde, 2, '.', ''));
        $compte->setDateModification(new \DateTime());
    }

    private function mettreAJourDepensePrevue(DepensePrevue $depensePrevue): void
    {
        // Mettre à jour le montant dépensé
        $depensePrevue->mettreAJourMontantDepense();
        
        // Mettre à jour le statut
        $depensePrevue->mettreAJourStatut();
        
        $this->entityManager->persist($depensePrevue);
    }
}
