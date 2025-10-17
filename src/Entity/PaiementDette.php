<?php

namespace App\Entity;

use App\Repository\PaiementDetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementDetteRepository::class)]
#[ORM\Table(name: 'paiement_dette')]
class PaiementDette
{
    // Types de paiement
    public const TYPE_PRINCIPAL = 'principal';
    public const TYPE_INTERET = 'interet';
    public const TYPE_MIXTE = 'mixte';

    public const TYPES = [
        self::TYPE_PRINCIPAL => 'Principal',
        self::TYPE_INTERET => 'Intérêt',
        self::TYPE_MIXTE => 'Principal + Intérêt',
    ];

    // Statuts de paiement
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRME = 'confirme';
    public const STATUT_ANNULE = 'annule';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE => 'En attente',
        self::STATUT_CONFIRME => 'Confirmé',
        self::STATUT_ANNULE => 'Annulé',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Dette::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Dette $dette = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantPrincipal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantInteret = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(length: 50)]
    private ?string $typePaiement = null;

    #[ORM\Column(length: 50)]
    private ?string $statutPaiement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateModification = null;

    public function __construct()
    {
        $this->datePaiement = new \DateTime();
        $this->dateCreation = new \DateTime();
        $this->dateModification = new \DateTime();
        $this->statutPaiement = self::STATUT_CONFIRME;
        $this->typePaiement = self::TYPE_MIXTE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getDette(): ?Dette
    {
        return $this->dette;
    }

    public function setDette(?Dette $dette): static
    {
        $this->dette = $dette;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getMontantPrincipal(): ?string
    {
        return $this->montantPrincipal;
    }

    public function setMontantPrincipal(?string $montantPrincipal): static
    {
        $this->montantPrincipal = $montantPrincipal;
        return $this;
    }

    public function getMontantInteret(): ?string
    {
        return $this->montantInteret;
    }

    public function setMontantInteret(?string $montantInteret): static
    {
        $this->montantInteret = $montantInteret;
        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function getTypePaiement(): ?string
    {
        return $this->typePaiement;
    }

    public function setTypePaiement(string $typePaiement): static
    {
        if (!in_array($typePaiement, array_keys(self::TYPES))) {
            throw new \InvalidArgumentException(sprintf('Type de paiement invalide: %s', $typePaiement));
        }

        $this->typePaiement = $typePaiement;
        return $this;
    }

    public function getTypePaiementLabel(): ?string
    {
        return $this->typePaiement ? self::TYPES[$this->typePaiement] : null;
    }

    public function getStatutPaiement(): ?string
    {
        return $this->statutPaiement;
    }

    public function setStatutPaiement(string $statutPaiement): static
    {
        if (!in_array($statutPaiement, array_keys(self::STATUTS))) {
            throw new \InvalidArgumentException(sprintf('Statut de paiement invalide: %s', $statutPaiement));
        }

        $this->statutPaiement = $statutPaiement;
        return $this;
    }

    public function getStatutPaiementLabel(): ?string
    {
        return $this->statutPaiement ? self::STATUTS[$this->statutPaiement] : null;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
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

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    // Méthodes de logique métier

    /**
     * Calcule automatiquement les montants principal et intérêt selon le type de paiement
     */
    public function calculerMontants(): void
    {
        if (!$this->dette || !$this->montant) {
            return;
        }

        $montantTotal = (float) $this->montant;
        $montantRestant = (float) $this->dette->calculerMontantRestant();
        $interetsRestants = (float) $this->dette->calculerInterets();

        switch ($this->typePaiement) {
            case self::TYPE_PRINCIPAL:
                $this->montantPrincipal = $this->montant;
                $this->montantInteret = '0.00';
                break;

            case self::TYPE_INTERET:
                $this->montantPrincipal = '0.00';
                $this->montantInteret = $this->montant;
                break;

            case self::TYPE_MIXTE:
            default:
                // Priorité aux intérêts, puis au principal
                if ($montantTotal <= $interetsRestants) {
                    $this->montantInteret = $this->montant;
                    $this->montantPrincipal = '0.00';
                } else {
                    $this->montantInteret = number_format($interetsRestants, 2, '.', '');
                    $this->montantPrincipal = number_format($montantTotal - $interetsRestants, 2, '.', '');
                }
                break;
        }
    }

    /**
     * Vérifie si le paiement est valide
     */
    public function estValide(): bool
    {
        if (!$this->montant || (float) $this->montant <= 0) {
            return false;
        }

        if (!$this->dette) {
            return false;
        }

        $montantRestant = (float) $this->dette->calculerMontantRestant();
        return (float) $this->montant <= $montantRestant;
    }

    /**
     * Applique le paiement à la dette
     */
    public function appliquerPaiement(): void
    {
        if (!$this->dette || !$this->estValide()) {
            return;
        }

        // Mettre à jour le montant effectif de la dette
        $montantEffectifActuel = (float) $this->dette->getMontantEffectif();
        $nouveauMontantEffectif = $montantEffectifActuel + (float) $this->montant;
        
        $this->dette->setMontantEffectif(number_format($nouveauMontantEffectif, 2, '.', ''));
        $this->dette->setDateDernierPaiement($this->datePaiement);
        
        // Mettre à jour le statut de la dette
        $this->dette->mettreAJourStatut();
        
        // Mettre à jour les montants calculés
        $this->dette->mettreAJourMontants();
    }
}
