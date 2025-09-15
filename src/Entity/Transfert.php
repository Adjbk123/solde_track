<?php

namespace App\Entity;

use App\Repository\TransfertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransfertRepository::class)]
#[ORM\Table(name: 'transfert')]
class Transfert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'transferts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Compte::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Compte $compteSource = null;

    #[ORM\ManyToOne(targetEntity: Compte::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Compte $compteDestination = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\ManyToOne(targetEntity: Devise::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->dateCreation = new \DateTime();
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

    public function getCompteSource(): ?Compte
    {
        return $this->compteSource;
    }

    public function setCompteSource(?Compte $compteSource): static
    {
        $this->compteSource = $compteSource;

        return $this;
    }

    public function getCompteDestination(): ?Compte
    {
        return $this->compteDestination;
    }

    public function setCompteDestination(?Compte $compteDestination): static
    {
        $this->compteDestination = $compteDestination;

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

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

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
     * Valide que le transfert est possible
     */
    public function isValid(): bool
    {
        // Vérifier que les comptes sont différents
        if ($this->compteSource === $this->compteDestination) {
            return false;
        }

        // Vérifier que les comptes appartiennent au même utilisateur
        if ($this->compteSource->getUser() !== $this->user || 
            $this->compteDestination->getUser() !== $this->user) {
            return false;
        }

        // Vérifier que les comptes ont la même devise
        if ($this->compteSource->getDevise() !== $this->devise || 
            $this->compteDestination->getDevise() !== $this->devise) {
            return false;
        }

        // Vérifier que le montant est positif
        if ((float) $this->montant <= 0) {
            return false;
        }

        // Vérifier que le compte source a suffisamment d'argent
        if ((float) $this->compteSource->getSoldeActuel() < (float) $this->montant) {
            return false;
        }

        return true;
    }

    /**
     * Exécute le transfert en mettant à jour les soldes
     */
    public function executer(): void
    {
        if (!$this->isValid()) {
            throw new \Exception('Transfert invalide');
        }

        $montantFloat = (float) $this->montant;

        // Débiter le compte source
        $nouveauSoldeSource = (float) $this->compteSource->getSoldeActuel() - $montantFloat;
        $this->compteSource->setSoldeActuel(number_format($nouveauSoldeSource, 2, '.', ''));

        // Créditer le compte destination
        $nouveauSoldeDestination = (float) $this->compteDestination->getSoldeActuel() + $montantFloat;
        $this->compteDestination->setSoldeActuel(number_format($nouveauSoldeDestination, 2, '.', ''));

        // Mettre à jour les dates de modification
        $this->compteSource->setDateModification(new \DateTime());
        $this->compteDestination->setDateModification(new \DateTime());
    }

    /**
     * Annule le transfert en inversant les opérations
     */
    public function annuler(): void
    {
        $montantFloat = (float) $this->montant;

        // Remettre l'argent dans le compte source
        $nouveauSoldeSource = (float) $this->compteSource->getSoldeActuel() + $montantFloat;
        $this->compteSource->setSoldeActuel(number_format($nouveauSoldeSource, 2, '.', ''));

        // Retirer l'argent du compte destination
        $nouveauSoldeDestination = (float) $this->compteDestination->getSoldeActuel() - $montantFloat;
        $this->compteDestination->setSoldeActuel(number_format($nouveauSoldeDestination, 2, '.', ''));

        // Mettre à jour les dates de modification
        $this->compteSource->setDateModification(new \DateTime());
        $this->compteDestination->setDateModification(new \DateTime());
    }

    /**
     * Retourne une description du transfert
     */
    public function getDescription(): string
    {
        return sprintf(
            'Transfert de %s %s de %s vers %s',
            number_format((float) $this->montant, 2, ',', ' '),
            $this->devise->getCode(),
            $this->compteSource->getNom(),
            $this->compteDestination->getNom()
        );
    }
}
