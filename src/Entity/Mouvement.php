<?php

namespace App\Entity;

use App\Repository\MouvementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementRepository::class)]
#[ORM\Table(name: 'mouvement')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'mouvement' => Mouvement::class,
    'depense' => Depense::class,
    'entree' => Entree::class,
    'dette' => Dette::class,
    'don' => Don::class,
])]
abstract class Mouvement
{
    public const TYPE_ENTREE = 'entree';
    public const TYPE_DEPENSE = 'depense';
    public const TYPE_DETTE_A_PAYER = 'dette_a_payer';
    public const TYPE_DETTE_A_RECEVOIR = 'dette_a_recevoir';
    public const TYPE_DON = 'don';

    public const TYPES = [
        self::TYPE_ENTREE => 'Entrée',
        self::TYPE_DEPENSE => 'Dépense',
        self::TYPE_DETTE_A_PAYER => 'Dette à payer',
        self::TYPE_DETTE_A_RECEVOIR => 'Dette à recevoir',
        self::TYPE_DON => 'Don',
    ];

    public const STATUT_NON_PAYE = 'non_paye';
    public const STATUT_PARTIELLEMENT_PAYE = 'partiellement_paye';
    public const STATUT_PAYE = 'paye';

    public const STATUTS = [
        self::STATUT_NON_PAYE => 'Non payé',
        self::STATUT_PARTIELLEMENT_PAYE => 'Partiellement payé',
        self::STATUT_PAYE => 'Payé',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'mouvements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantEffectif = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'mouvements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'mouvements')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Projet $projet = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'mouvements')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Compte::class, inversedBy: 'mouvements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Compte $compte = null;

    #[ORM\OneToMany(mappedBy: 'mouvement', targetEntity: Paiement::class, orphanRemoval: true)]
    private Collection $paiements;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->montantEffectif = '0.00';
        $this->statut = self::STATUT_NON_PAYE;
        $this->paiements = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, array_keys(self::TYPES))) {
            throw new \InvalidArgumentException(sprintf('Type invalide: %s', $type));
        }

        $this->type = $type;

        return $this;
    }

    public function getTypeLabel(): ?string
    {
        return $this->type ? self::TYPES[$this->type] : null;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function getMontantEffectif(): ?string
    {
        return $this->montantEffectif;
    }

    public function setMontantEffectif(?string $montantEffectif): static
    {
        $this->montantEffectif = $montantEffectif;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

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

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(?Projet $projet): static
    {
        $this->projet = $projet;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getCompte(): ?Compte
    {
        return $this->compte;
    }

    public function setCompte(?Compte $compte): static
    {
        $this->compte = $compte;

        return $this;
    }

    /**
     * Calcule le montant restant à payer/recevoir
     */
    public function getMontantRestant(): string
    {
        $total = (float) $this->montantTotal;
        $effectif = (float) $this->montantEffectif;
        
        return number_format($total - $effectif, 2, '.', '');
    }

    /**
     * Met à jour le statut en fonction du montant effectif
     */
    public function updateStatut(): void
    {
        $total = (float) $this->montantTotal;
        $effectif = (float) $this->montantEffectif;

        if ($effectif == 0) {
            $this->statut = self::STATUT_NON_PAYE;
        } elseif ($effectif < $total) {
            $this->statut = self::STATUT_PARTIELLEMENT_PAYE;
        } else {
            $this->statut = self::STATUT_PAYE;
        }
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setMouvement($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getMouvement() === $this) {
                $paiement->setMouvement(null);
            }
        }

        return $this;
    }
}
