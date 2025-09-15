<?php

namespace App\Entity;

use App\Repository\CompteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteRepository::class)]
#[ORM\Table(name: 'compte')]
class Compte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comptes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Devise::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $soldeInitial = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $soldeActuel = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $institution = null;

    #[ORM\OneToMany(mappedBy: 'compte', targetEntity: Mouvement::class, orphanRemoval: true)]
    private Collection $mouvements;

    public function __construct()
    {
        $this->mouvements = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->actif = true;
        $this->soldeInitial = '0.00';
        $this->soldeActuel = '0.00';
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

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getSoldeInitial(): ?string
    {
        return $this->soldeInitial;
    }

    public function setSoldeInitial(string $soldeInitial): static
    {
        $this->soldeInitial = $soldeInitial;

        return $this;
    }

    public function getSoldeActuel(): ?string
    {
        return $this->soldeActuel;
    }

    public function setSoldeActuel(string $soldeActuel): static
    {
        $this->soldeActuel = $soldeActuel;

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

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getInstitution(): ?string
    {
        return $this->institution;
    }

    public function setInstitution(?string $institution): static
    {
        $this->institution = $institution;

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
            $mouvement->setCompte($this);
        }

        return $this;
    }

    public function removeMouvement(Mouvement $mouvement): static
    {
        if ($this->mouvements->removeElement($mouvement)) {
            // set the owning side to null (unless already changed)
            if ($mouvement->getCompte() === $this) {
                $mouvement->setCompte(null);
            }
        }

        return $this;
    }

    // Constantes pour les types de comptes
    public const TYPE_COMPTE_PRINCIPAL = 'compte_principal';
    public const TYPE_EPARGNE = 'epargne';
    public const TYPE_MOMO = 'momo';
    public const TYPE_CARTE = 'carte';
    public const TYPE_ESPECES = 'especes';
    public const TYPE_BANQUE = 'banque';
    public const TYPE_CRYPTO = 'crypto';
    public const TYPE_AUTRE = 'autre';

    public static function getTypes(): array
    {
        return [
            self::TYPE_COMPTE_PRINCIPAL => 'Compte Principal',
            self::TYPE_EPARGNE => 'Épargne',
            self::TYPE_MOMO => 'Mobile Money',
            self::TYPE_CARTE => 'Carte Bancaire',
            self::TYPE_ESPECES => 'Espèces',
            self::TYPE_BANQUE => 'Compte Bancaire',
            self::TYPE_CRYPTO => 'Cryptomonnaie',
            self::TYPE_AUTRE => 'Autre'
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? 'Inconnu';
    }

    /**
     * Calcule le solde actuel basé sur les mouvements
     */
    public function calculerSoldeActuel(): string
    {
        $solde = (float) $this->soldeInitial;
        
        foreach ($this->mouvements as $mouvement) {
            $montant = (float) $mouvement->getMontantEffectif();
            
            // Les entrées et dettes à recevoir augmentent le solde
            if (in_array($mouvement->getType(), [Mouvement::TYPE_ENTREE, Mouvement::TYPE_DETTE_A_RECEVOIR])) {
                $solde += $montant;
            }
            // Les dépenses, dettes à payer et dons diminuent le solde
            elseif (in_array($mouvement->getType(), [Mouvement::TYPE_DEPENSE, Mouvement::TYPE_DETTE_A_PAYER, Mouvement::TYPE_DON])) {
                $solde -= $montant;
            }
        }
        
        return number_format($solde, 2, '.', '');
    }

    /**
     * Met à jour le solde actuel
     */
    public function mettreAJourSolde(): void
    {
        $this->soldeActuel = $this->calculerSoldeActuel();
        $this->dateModification = new \DateTime();
    }
}
