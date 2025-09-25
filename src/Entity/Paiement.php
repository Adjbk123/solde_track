<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
class Paiement
{
    use TimestampableTrait;
    public const STATUT_RECU = 'recu';
    public const STATUT_PAYE = 'paye';

    public const STATUTS = [
        self::STATUT_RECU => 'Reçu',
        self::STATUT_PAYE => 'Payé',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Mouvement::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mouvement $mouvement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->statut = self::STATUT_PAYE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMouvement(): ?Mouvement
    {
        return $this->mouvement;
    }

    public function setMouvement(?Mouvement $mouvement): static
    {
        $this->mouvement = $mouvement;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
