<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\DepensePrevueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepensePrevueRepository::class)]
#[ORM\Table(name: 'depense_prevue')]
class DepensePrevue
{
    use TimestampableTrait;

    // Statuts de la dépense prévue
    public const STATUT_PLANIFIE = 'planifie';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINE = 'termine';
    public const STATUT_ANNULE = 'annule';
    public const STATUT_EN_RETARD = 'en_retard';

    public const STATUTS = [
        self::STATUT_PLANIFIE => 'Planifié',
        self::STATUT_EN_COURS => 'En cours',
        self::STATUT_TERMINE => 'Terminé',
        self::STATUT_ANNULE => 'Annulé',
        self::STATUT_EN_RETARD => 'En retard',
    ];

    // Types de budget
    public const TYPE_BUDGET_CERTAIN = 'certain';
    public const TYPE_BUDGET_ESTIME = 'estime';
    public const TYPE_BUDGET_INCONNU = 'inconnu';

    public const TYPES_BUDGET = [
        self::TYPE_BUDGET_CERTAIN => 'Montant certain',
        self::TYPE_BUDGET_ESTIME => 'Montant estimé',
        self::TYPE_BUDGET_INCONNU => 'Montant à définir',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'depensesPrevues')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budgetPrevu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeBudget = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantDepense = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutPrevue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinPrevue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutReelle = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinReelle = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'depensePrevue', targetEntity: Mouvement::class)]
    private Collection $mouvements;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->montantDepense = '0.00';
        $this->statut = self::STATUT_PLANIFIE;
        $this->typeBudget = self::TYPE_BUDGET_INCONNU;
        $this->mouvements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getBudgetPrevu(): ?string
    {
        return $this->budgetPrevu;
    }

    public function setBudgetPrevu(?string $budgetPrevu): static
    {
        $this->budgetPrevu = $budgetPrevu;
        return $this;
    }

    public function getTypeBudget(): ?string
    {
        return $this->typeBudget;
    }

    public function setTypeBudget(?string $typeBudget): static
    {
        if ($typeBudget && !in_array($typeBudget, array_keys(self::TYPES_BUDGET))) {
            throw new \InvalidArgumentException(sprintf('Type de budget invalide: %s', $typeBudget));
        }
        $this->typeBudget = $typeBudget;
        return $this;
    }

    public function getTypeBudgetLabel(): ?string
    {
        return $this->typeBudget ? self::TYPES_BUDGET[$this->typeBudget] : null;
    }

    public function getMontantDepense(): ?string
    {
        return $this->montantDepense;
    }

    public function setMontantDepense(string $montantDepense): static
    {
        $this->montantDepense = $montantDepense;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (!in_array($statut, array_keys(self::STATUTS))) {
            throw new \InvalidArgumentException(sprintf('Statut invalide: %s', $statut));
        }
        $this->statut = $statut;
        return $this;
    }

    public function getStatutLabel(): ?string
    {
        return $this->statut ? self::STATUTS[$this->statut] : null;
    }

    public function getDateDebutPrevue(): ?\DateTimeInterface
    {
        return $this->dateDebutPrevue;
    }

    public function setDateDebutPrevue(?\DateTimeInterface $dateDebutPrevue): static
    {
        $this->dateDebutPrevue = $dateDebutPrevue;
        return $this;
    }

    public function getDateFinPrevue(): ?\DateTimeInterface
    {
        return $this->dateFinPrevue;
    }

    public function setDateFinPrevue(?\DateTimeInterface $dateFinPrevue): static
    {
        $this->dateFinPrevue = $dateFinPrevue;
        return $this;
    }

    public function getDateDebutReelle(): ?\DateTimeInterface
    {
        return $this->dateDebutReelle;
    }

    public function setDateDebutReelle(?\DateTimeInterface $dateDebutReelle): static
    {
        $this->dateDebutReelle = $dateDebutReelle;
        return $this;
    }

    public function getDateFinReelle(): ?\DateTimeInterface
    {
        return $this->dateFinReelle;
    }

    public function setDateFinReelle(?\DateTimeInterface $dateFinReelle): static
    {
        $this->dateFinReelle = $dateFinReelle;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return Collection<int, Mouvement>
     */
    public function getMouvements(): Collection
    {
        return $this->mouvements;
    }

    public function addMouvement(Mouvement $mouvement): static
    {
        if (!$this->mouvements->contains($mouvement)) {
            $this->mouvements->add($mouvement);
            $mouvement->setDepensePrevue($this);
        }
        return $this;
    }

    public function removeMouvement(Mouvement $mouvement): static
    {
        if ($this->mouvements->removeElement($mouvement)) {
            if ($mouvement->getDepensePrevue() === $this) {
                $mouvement->setDepensePrevue(null);
            }
        }
        return $this;
    }

    // Méthodes de logique métier

    /**
     * Calcule le montant total dépensé pour cette dépense prévue
     */
    public function calculerMontantDepense(): float
    {
        $total = 0;
        foreach ($this->mouvements as $mouvement) {
            if ($mouvement->getType() === 'sortie' || $mouvement->getType() === 'don') {
                $total += (float) $mouvement->getMontantTotal();
            }
        }
        return $total;
    }

    /**
     * Calcule le pourcentage d'avancement budgétaire
     */
    public function getPourcentageAvancement(): float
    {
        if (!$this->budgetPrevu || (float) $this->budgetPrevu <= 0) {
            return 0;
        }

        $montantDepense = (float) $this->montantDepense;
        $budgetPrevu = (float) $this->budgetPrevu;

        return min(100, ($montantDepense / $budgetPrevu) * 100);
    }

    /**
     * Vérifie si le budget est dépassé
     */
    public function isBudgetDepasse(): bool
    {
        if (!$this->budgetPrevu || $this->typeBudget === self::TYPE_BUDGET_INCONNU) {
            return false;
        }

        return (float) $this->montantDepense > (float) $this->budgetPrevu;
    }

    /**
     * Calcule le montant restant du budget
     */
    public function getMontantRestant(): float
    {
        if (!$this->budgetPrevu || $this->typeBudget === self::TYPE_BUDGET_INCONNU) {
            return 0;
        }

        $budgetPrevu = (float) $this->budgetPrevu;
        $montantDepense = (float) $this->montantDepense;

        return max(0, $budgetPrevu - $montantDepense);
    }

    /**
     * Vérifie si la dépense prévue est en retard
     */
    public function isEnRetard(): bool
    {
        if (!$this->dateFinPrevue || $this->statut === self::STATUT_TERMINE) {
            return false;
        }

        return $this->dateFinPrevue < new \DateTime();
    }

    /**
     * Met à jour le statut de la dépense prévue selon les critères
     */
    public function mettreAJourStatut(): void
    {
        // Si la dépense est annulée ou terminée, ne pas changer le statut
        if (in_array($this->statut, [self::STATUT_ANNULE, self::STATUT_TERMINE])) {
            return;
        }

        // Vérifier si la dépense est en retard
        if ($this->isEnRetard()) {
            $this->setStatut(self::STATUT_EN_RETARD);
            return;
        }

        // Si le budget est dépassé et la dépense n'est pas terminée
        if ($this->isBudgetDepasse() && $this->statut !== self::STATUT_TERMINE) {
            $this->setStatut(self::STATUT_EN_RETARD);
            return;
        }

        // Si la dépense a des mouvements, elle est en cours
        if ($this->mouvements->count() > 0 && $this->statut === self::STATUT_PLANIFIE) {
            $this->setStatut(self::STATUT_EN_COURS);
        }
    }

    /**
     * Met à jour le montant dépensé
     */
    public function mettreAJourMontantDepense(): void
    {
        $this->montantDepense = number_format($this->calculerMontantDepense(), 2, '.', '');
    }

    /**
     * Démarre la dépense prévue
     */
    public function demarrer(): void
    {
        if ($this->statut === self::STATUT_PLANIFIE) {
            $this->setStatut(self::STATUT_EN_COURS);
            $this->setDateDebutReelle(new \DateTime());
        }
    }

    /**
     * Termine la dépense prévue
     */
    public function terminer(): void
    {
        if (in_array($this->statut, [self::STATUT_PLANIFIE, self::STATUT_EN_COURS, self::STATUT_EN_RETARD])) {
            $this->setStatut(self::STATUT_TERMINE);
            $this->setDateFinReelle(new \DateTime());
        }
    }

    /**
     * Annule la dépense prévue
     */
    public function annuler(): void
    {
        if ($this->statut !== self::STATUT_TERMINE) {
            $this->setStatut(self::STATUT_ANNULE);
        }
    }

    /**
     * Vérifie si la dépense prévue a un budget défini
     */
    public function hasBudgetDefini(): bool
    {
        return $this->budgetPrevu && $this->typeBudget !== self::TYPE_BUDGET_INCONNU;
    }

    /**
     * Retourne une description du budget
     */
    public function getDescriptionBudget(): string
    {
        if (!$this->hasBudgetDefini()) {
            return 'Montant à définir';
        }

        $montant = number_format((float) $this->budgetPrevu, 0, ',', ' ') . ' F';
        
        return match($this->typeBudget) {
            self::TYPE_BUDGET_CERTAIN => "Montant certain : {$montant}",
            self::TYPE_BUDGET_ESTIME => "Montant estimé : {$montant}",
            default => $montant
        };
    }

    /**
     * Retourne la date de création
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Retourne la date de modification
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
