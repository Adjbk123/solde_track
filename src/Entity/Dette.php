<?php

namespace App\Entity;

use App\Repository\DetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetteRepository::class)]
#[ORM\Table(name: 'dette')]
class Dette extends Mouvement
{
    // L'ID est hérité de Mouvement, pas besoin de le redéfinir

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $echeance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $taux = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $montantRest = null;

    public function __construct()
    {
        parent::__construct();
        // Le type sera défini selon le contexte (dette à payer ou à recevoir)
    }

    // Les méthodes de Mouvement sont héritées automatiquement

    public function getEcheance(): ?\DateTimeInterface
    {
        return $this->echeance;
    }

    public function setEcheance(?\DateTimeInterface $echeance): static
    {
        $this->echeance = $echeance;

        return $this;
    }

    public function getTaux(): ?string
    {
        return $this->taux;
    }

    public function setTaux(?string $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getMontantRest(): ?string
    {
        return $this->montantRest;
    }

    public function setMontantRest(?string $montantRest): static
    {
        $this->montantRest = $montantRest;

        return $this;
    }

    /**
     * Calcule le montant des intérêts si applicable
     */
    public function getMontantInterets(): string
    {
        if (!$this->taux || !$this->montantRest) {
            return '0.00';
        }

        $montant = (float) $this->montantRest;
        $taux = (float) $this->taux;
        
        return number_format(($montant * $taux) / 100, 2, '.', '');
    }

    /**
     * Vérifie si la dette est en retard
     */
    public function isEnRetard(): bool
    {
        if (!$this->echeance) {
            return false;
        }

        return $this->echeance < new \DateTime() && $this->getStatut() !== Mouvement::STATUT_PAYE;
    }

    /**
     * Met à jour le montant restant automatiquement
     */
    public function updateMontantRest(): void
    {
        $this->montantRest = $this->getMontantRestant();
    }
}
