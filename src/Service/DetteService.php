<?php

namespace App\Service;

use App\Entity\Dette;
use App\Entity\PaiementDette;
use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Compte;
use App\Entity\Categorie;
use App\Repository\DetteRepository;
use App\Repository\PaiementDetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DetteService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private DetteRepository $detteRepository,
        private PaiementDetteRepository $paiementRepository
    ) {}

    /**
     * Crée une nouvelle dette
     */
    public function creerDette(User $user, array $donnees): Dette
    {
        // Validation des données requises
        $this->validerDonneesDette($donnees);

        // Récupération des entités liées
        $contact = $this->recupererContact($user, $donnees['contact_id'] ?? null);
        $compte = $this->recupererCompte($user, $donnees['compte_id'] ?? null);
        $categorie = $this->recupererCategorie($user, $donnees['categorie_id']);

        // Création de la dette
        $dette = new Dette();
        $dette->setUser($user);
        $dette->setMontantPrincipal($donnees['montantPrincipal']);
        $dette->setTauxInteret($donnees['tauxInteret'] ?? null);
        $dette->setTypeDette($donnees['typeDette']);
        $dette->setDescription($donnees['description'] ?? null);
        $dette->setNotes($donnees['notes'] ?? null);
        $dette->setContact($contact);
        $dette->setCompte($compte);
        $dette->setCategorie($categorie);

        // Gestion des dates
        if (isset($donnees['date'])) {
            $dette->setDate(new \DateTime($donnees['date']));
        }
        if (isset($donnees['dateEcheance'])) {
            $dette->setDateEcheance(new \DateTime($donnees['dateEcheance']));
        }

        // Gestion des paramètres optionnels
        if (isset($donnees['typeCalculInteret'])) {
            $dette->setTypeCalculInteret($donnees['typeCalculInteret']);
        }
        if (isset($donnees['notificationsActivees'])) {
            $dette->setNotificationsActivees($donnees['notificationsActivees']);
        }
        if (isset($donnees['joursAlerteEcheance'])) {
            $dette->setJoursAlerteEcheance($donnees['joursAlerteEcheance']);
        }

        // Calculs automatiques
        $dette->mettreAJourMontants();

        // Validation de l'entité
        $this->validerEntite($dette);

        // Persistance
        $this->entityManager->persist($dette);
        $this->entityManager->flush();

        return $dette;
    }

    /**
     * Met à jour une dette existante
     */
    public function mettreAJourDette(Dette $dette, array $donnees): Dette
    {
        // Mise à jour des champs modifiables
        if (isset($donnees['description'])) {
            $dette->setDescription($donnees['description']);
        }
        if (isset($donnees['notes'])) {
            $dette->setNotes($donnees['notes']);
        }
        if (isset($donnees['tauxInteret'])) {
            $dette->setTauxInteret($donnees['tauxInteret']);
        }
        if (isset($donnees['dateEcheance'])) {
            $dette->setDateEcheance(new \DateTime($donnees['dateEcheance']));
        }
        if (isset($donnees['typeCalculInteret'])) {
            $dette->setTypeCalculInteret($donnees['typeCalculInteret']);
        }
        if (isset($donnees['notificationsActivees'])) {
            $dette->setNotificationsActivees($donnees['notificationsActivees']);
        }
        if (isset($donnees['joursAlerteEcheance'])) {
            $dette->setJoursAlerteEcheance($donnees['joursAlerteEcheance']);
        }

        // Recalcul des montants
        $dette->mettreAJourMontants();
        $dette->mettreAJourStatut();

        // Validation
        $this->validerEntite($dette);

        // Persistance
        $this->entityManager->flush();

        return $dette;
    }

    /**
     * Supprime une dette
     */
    public function supprimerDette(Dette $dette): void
    {
        // Vérifier qu'il n'y a pas de paiements associés
        if ($dette->getPaiements()->count() > 0) {
            throw new \InvalidArgumentException('Impossible de supprimer une dette avec des paiements associés');
        }

        $this->entityManager->remove($dette);
        $this->entityManager->flush();
    }

    /**
     * Enregistre un paiement pour une dette
     */
    public function enregistrerPaiement(Dette $dette, array $donnees): PaiementDette
    {
        // Validation des données
        $this->validerDonneesPaiement($donnees);

        // Création du paiement
        $paiement = new PaiementDette();
        $paiement->setUtilisateur($dette->getUser());
        $paiement->setDette($dette);
        $paiement->setMontant($donnees['montant']);
        $paiement->setTypePaiement($donnees['typePaiement'] ?? PaiementDette::TYPE_MIXTE);
        $paiement->setCommentaire($donnees['commentaire'] ?? null);
        $paiement->setReference($donnees['reference'] ?? null);

        // Gestion de la date
        if (isset($donnees['datePaiement'])) {
            $paiement->setDatePaiement(new \DateTime($donnees['datePaiement']));
        }

        // Calcul automatique des montants
        $paiement->calculerMontants();

        // Validation
        if (!$paiement->estValide()) {
            throw new \InvalidArgumentException('Le paiement n\'est pas valide');
        }

        // Persistance
        $this->entityManager->persist($paiement);
        
        // Application du paiement à la dette
        $paiement->appliquerPaiement();
        
        $this->entityManager->flush();

        return $paiement;
    }

    /**
     * Annule un paiement
     */
    public function annulerPaiement(PaiementDette $paiement): void
    {
        if ($paiement->getStatutPaiement() === PaiementDette::STATUT_ANNULE) {
            throw new \InvalidArgumentException('Le paiement est déjà annulé');
        }

        $paiement->setStatutPaiement(PaiementDette::STATUT_ANNULE);
        
        // Recalculer le montant effectif de la dette
        $dette = $paiement->getDette();
        $montantEffectifActuel = (float) $dette->getMontantEffectif();
        $nouveauMontantEffectif = $montantEffectifActuel - (float) $paiement->getMontant();
        
        $dette->setMontantEffectif(number_format($nouveauMontantEffectif, 2, '.', ''));
        $dette->mettreAJourStatut();
        $dette->mettreAJourMontants();

        $this->entityManager->flush();
    }

    /**
     * Récupère les dettes d'un utilisateur avec filtres
     */
    public function recupererDettes(User $user, array $filtres = []): array
    {
        return $this->detteRepository->findByUser($user, $filtres);
    }

    /**
     * Récupère une dette par ID
     */
    public function recupererDette(User $user, int $id): ?Dette
    {
        $dette = $this->detteRepository->find($id);
        
        if (!$dette || $dette->getUser() !== $user) {
            return null;
        }

        return $dette;
    }

    /**
     * Récupère les dettes en retard
     */
    public function recupererDettesEnRetard(User $user): array
    {
        return $this->detteRepository->findDettesEnRetard($user);
    }

    /**
     * Récupère les dettes avec échéance proche
     */
    public function recupererDettesEcheanceProche(User $user, int $jours = 7): array
    {
        return $this->detteRepository->findDettesEcheanceProche($user, $jours);
    }

    /**
     * Calcule le résumé des dettes d'un utilisateur
     */
    public function calculerResumeDettes(User $user): array
    {
        $totalPrets = $this->detteRepository->getTotalParType($user, Dette::TYPE_PRET);
        $totalEmprunts = $this->detteRepository->getTotalParType($user, Dette::TYPE_EMPRUNT);
        $totalCreances = $this->detteRepository->getTotalParType($user, Dette::TYPE_CREANCE);

        $totalPayePrets = $this->detteRepository->getTotalPayeParType($user, Dette::TYPE_PRET);
        $totalPayeEmprunts = $this->detteRepository->getTotalPayeParType($user, Dette::TYPE_EMPRUNT);
        $totalPayeCreances = $this->detteRepository->getTotalPayeParType($user, Dette::TYPE_CREANCE);

        $dettesEnRetard = $this->recupererDettesEnRetard($user);
        $dettesEcheanceProche = $this->recupererDettesEcheanceProche($user);

        return [
            'totaux' => [
                'prets' => [
                    'total' => $totalPrets,
                    'paye' => $totalPayePrets,
                    'restant' => $totalPrets - $totalPayePrets
                ],
                'emprunts' => [
                    'total' => $totalEmprunts,
                    'paye' => $totalPayeEmprunts,
                    'restant' => $totalEmprunts - $totalPayeEmprunts
                ],
                'creances' => [
                    'total' => $totalCreances,
                    'paye' => $totalPayeCreances,
                    'restant' => $totalCreances - $totalPayeCreances
                ]
            ],
            'alertes' => [
                'en_retard' => count($dettesEnRetard),
                'echeance_proche' => count($dettesEcheanceProche)
            ],
            'solde_net' => ($totalPrets + $totalCreances) - $totalEmprunts
        ];
    }

    /**
     * Met à jour automatiquement les statuts des dettes
     */
    public function mettreAJourStatutsDettes(User $user): int
    {
        $dettes = $this->detteRepository->findByUser($user);
        $compteur = 0;

        foreach ($dettes as $dette) {
            $ancienStatut = $dette->getStatutDette();
            $dette->mettreAJourStatut();
            
            if ($ancienStatut !== $dette->getStatutDette()) {
                $compteur++;
            }
        }

        $this->entityManager->flush();
        return $compteur;
    }

    // Méthodes privées

    private function validerDonneesDette(array $donnees): void
    {
        if (!isset($donnees['montantPrincipal']) || !isset($donnees['typeDette']) || !isset($donnees['categorie_id'])) {
            throw new \InvalidArgumentException('Données manquantes: montantPrincipal, typeDette et categorie_id sont requis');
        }

        if (!in_array($donnees['typeDette'], array_keys(Dette::TYPES))) {
            throw new \InvalidArgumentException('Type de dette invalide');
        }

        if ((float) $donnees['montantPrincipal'] <= 0) {
            throw new \InvalidArgumentException('Le montant principal doit être positif');
        }
    }

    private function validerDonneesPaiement(array $donnees): void
    {
        if (!isset($donnees['montant'])) {
            throw new \InvalidArgumentException('Le montant du paiement est requis');
        }

        if ((float) $donnees['montant'] <= 0) {
            throw new \InvalidArgumentException('Le montant du paiement doit être positif');
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

    private function recupererCategorie(User $user, int $categorieId): Categorie
    {
        $categorie = $this->entityManager->getRepository(Categorie::class)->find($categorieId);
        if (!$categorie || $categorie->getUser() !== $user) {
            throw new \InvalidArgumentException('Catégorie non trouvée ou non autorisée');
        }

        return $categorie;
    }
}
