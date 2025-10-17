<?php

namespace App\Entity;

use App\Repository\DetteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetteRepository::class)]
#[ORM\Table(name: 'dette')]
class Dette extends Mouvement
{
    // Types de dettes
    public const TYPE_PRET = 'pret';
    public const TYPE_EMPRUNT = 'emprunt';
    public const TYPE_CREANCE = 'creance';

    public const TYPES = [
        self::TYPE_PRET => 'Prêt',
        self::TYPE_EMPRUNT => 'Emprunt',
        self::TYPE_CREANCE => 'Créance',
    ];

    // Statuts des dettes
    public const STATUT_ACTIVE = 'active';
    public const STATUT_PAYEE = 'payee';
    public const STATUT_EN_RETARD = 'en_retard';
    public const STATUT_ANNULEE = 'annulee';

    public const STATUTS = [
        self::STATUT_ACTIVE => 'Active',
        self::STATUT_PAYEE => 'Payée',
        self::STATUT_EN_RETARD => 'En retard',
        self::STATUT_ANNULEE => 'Annulée',
    ];

    // Types de calcul d'intérêts
    public const INTERET_SIMPLE = 'simple';
    public const INTERET_COMPOSE = 'compose';

    public const TYPES_INTERET = [
        self::INTERET_SIMPLE => 'Intérêt simple',
        self::INTERET_COMPOSE => 'Intérêt composé',
    ];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantPrincipal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxInteret = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEcheance = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDernierPaiement = null;

    #[ORM\Column(length: 50)]
    private ?string $typeDette = null;

    #[ORM\Column(length: 50)]
    private ?string $statutDette = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeCalculInteret = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantInterets = null;

    // montantTotal est hérité de Mouvement

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $notificationsActivees = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $joursAlerteEcheance = null;

    #[ORM\OneToMany(mappedBy: 'dette', targetEntity: PaiementDette::class, orphanRemoval: true)]
    private Collection $paiements;

    public function __construct()
    {
        parent::__construct();
        $this->statutDette = self::STATUT_ACTIVE;
        $this->typeCalculInteret = self::INTERET_SIMPLE;
        $this->notificationsActivees = true;
        $this->joursAlerteEcheance = 7;
        $this->paiements = new ArrayCollection();
    }

    public function getMontantPrincipal(): ?string
    {
        return $this->montantPrincipal;
    }

    public function setMontantPrincipal(string $montantPrincipal): static
    {
        $this->montantPrincipal = $montantPrincipal;
        return $this;
    }

    public function getTauxInteret(): ?string
    {
        return $this->tauxInteret;
    }

    public function setTauxInteret(?string $tauxInteret): static
    {
        $this->tauxInteret = $tauxInteret;
        return $this;
    }

    public function getDateEcheance(): ?\DateTimeInterface
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTimeInterface $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getDateDernierPaiement(): ?\DateTimeInterface
    {
        return $this->dateDernierPaiement;
    }

    public function setDateDernierPaiement(?\DateTimeInterface $dateDernierPaiement): static
    {
        $this->dateDernierPaiement = $dateDernierPaiement;
        return $this;
    }

    public function getTypeDette(): ?string
    {
        return $this->typeDette;
    }

    public function setTypeDette(string $typeDette): static
    {
        if (!in_array($typeDette, array_keys(self::TYPES))) {
            throw new \InvalidArgumentException(sprintf('Type de dette invalide: %s', $typeDette));
        }

        $this->typeDette = $typeDette;
        return $this;
    }

    public function getTypeDetteLabel(): ?string
    {
        return $this->typeDette ? self::TYPES[$this->typeDette] : null;
    }

    public function getStatutDette(): ?string
    {
        return $this->statutDette;
    }

    public function setStatutDette(string $statutDette): static
    {
        if (!in_array($statutDette, array_keys(self::STATUTS))) {
            throw new \InvalidArgumentException(sprintf('Statut de dette invalide: %s', $statutDette));
        }

        $this->statutDette = $statutDette;
        return $this;
    }

    public function getStatutDetteLabel(): ?string
    {
        return $this->statutDette ? self::STATUTS[$this->statutDette] : null;
    }

    public function getTypeCalculInteret(): ?string
    {
        return $this->typeCalculInteret;
    }

    public function setTypeCalculInteret(?string $typeCalculInteret): static
    {
        if ($typeCalculInteret && !in_array($typeCalculInteret, array_keys(self::TYPES_INTERET))) {
            throw new \InvalidArgumentException(sprintf('Type de calcul d\'intérêt invalide: %s', $typeCalculInteret));
        }

        $this->typeCalculInteret = $typeCalculInteret;
        return $this;
    }

    public function getTypeCalculInteretLabel(): ?string
    {
        return $this->typeCalculInteret ? self::TYPES_INTERET[$this->typeCalculInteret] : null;
    }

    public function getMontantInterets(): ?string
    {
        return $this->montantInterets;
    }

    public function setMontantInterets(?string $montantInterets): static
    {
        $this->montantInterets = $montantInterets;
        return $this;
    }

    // getMontantTotal() et setMontantTotal() sont hérités de Mouvement

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getNotificationsActivees(): ?bool
    {
        return $this->notificationsActivees;
    }

    public function setNotificationsActivees(bool $notificationsActivees): static
    {
        $this->notificationsActivees = $notificationsActivees;
        return $this;
    }

    public function getJoursAlerteEcheance(): ?int
    {
        return $this->joursAlerteEcheance;
    }

    public function setJoursAlerteEcheance(?int $joursAlerteEcheance): static
    {
        $this->joursAlerteEcheance = $joursAlerteEcheance;
        return $this;
    }

    /**
     * @return Collection<int, PaiementDette>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(PaiementDette $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setDette($this);
        }

        return $this;
    }

    public function removePaiement(PaiementDette $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getDette() === $this) {
                $paiement->setDette(null);
            }
        }

        return $this;
    }

    // Méthodes de calcul et de logique métier

    /**
     * Calcule le montant total des intérêts accumulés
     */
    public function calculerInterets(): string
    {
        if (!$this->tauxInteret || !$this->montantPrincipal) {
            return '0.00';
        }

        $principal = (float) $this->montantPrincipal;
        $taux = (float) $this->tauxInteret;
        $dateDebut = $this->getDate();
        if (!$dateDebut) {
            return '0.00';
        }
        
        $dateFin = $this->dateEcheance ?? new \DateTime();
        $jours = $dateFin->diff($dateDebut)->days;

        if ($this->typeCalculInteret === self::INTERET_COMPOSE) {
            // Intérêt composé : I = P * ((1 + r/100)^n - 1)
            $interets = $principal * (pow(1 + ($taux / 100), $jours / 365) - 1);
        } else {
            // Intérêt simple : I = P * r * t
            $interets = $principal * ($taux / 100) * ($jours / 365);
        }

        return number_format($interets, 2, '.', '');
    }

    /**
     * Calcule le montant total de la dette (principal + intérêts)
     */
    public function calculerMontantTotal(): string
    {
        if (!$this->montantPrincipal) {
            return '0.00';
        }
        
        $principal = (float) $this->montantPrincipal;
        $interets = (float) $this->calculerInterets();
        
        return number_format($principal + $interets, 2, '.', '');
    }

    /**
     * Calcule le montant restant à payer
     */
    public function calculerMontantRestant(): string
    {
        $totalStr = $this->calculerMontantTotal();
        if (!$totalStr || $totalStr === '0.00') {
            return '0.00';
        }
        
        $total = (float) $totalStr;
        $paye = (float) ($this->getMontantEffectif() ?? '0');
        
        return number_format($total - $paye, 2, '.', '');
    }

    /**
     * Vérifie si la dette est en retard
     */
    public function estEnRetard(): bool
    {
        if (!$this->dateEcheance || $this->statutDette === self::STATUT_PAYEE) {
            return false;
        }

        return $this->dateEcheance < new \DateTime();
    }

    /**
     * Vérifie si l'échéance est proche
     */
    public function echeanceProche(): bool
    {
        if (!$this->dateEcheance || $this->statutDette === self::STATUT_PAYEE) {
            return false;
        }

        $joursRestants = $this->dateEcheance->diff(new \DateTime())->days;
        return $joursRestants <= $this->joursAlerteEcheance && $joursRestants >= 0;
    }

    /**
     * Met à jour le statut de la dette automatiquement
     */
    public function mettreAJourStatut(): void
    {
        if ($this->estEnRetard()) {
            $this->statutDette = self::STATUT_EN_RETARD;
        } elseif ((float) $this->calculerMontantRestant() <= 0) {
            $this->statutDette = self::STATUT_PAYEE;
        } else {
            $this->statutDette = self::STATUT_ACTIVE;
        }
    }

    /**
     * Met à jour les montants calculés
     */
    public function mettreAJourMontants(): void
    {
        $this->montantInterets = $this->calculerInterets();
        $this->setMontantTotal($this->calculerMontantTotal());
    }
}