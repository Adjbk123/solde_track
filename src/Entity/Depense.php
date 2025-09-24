<?php

namespace App\Entity;

use App\Repository\DepenseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepenseRepository::class)]
#[ORM\Table(name: 'depense')]
class Depense extends Mouvement
{
    public const METHODE_CASH = 'cash';
    public const METHODE_MOMO = 'momo';
    public const METHODE_CARTE = 'carte';
    public const METHODE_AUTRE = 'autre';

    public const METHODES = [
        self::METHODE_CASH => 'Cash',
        self::METHODE_MOMO => 'MoMo',
        self::METHODE_CARTE => 'Carte',
        self::METHODE_AUTRE => 'Autre',
    ];

    // L'ID est hérité de Mouvement, pas besoin de le redéfinir

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $methodePaiement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recu = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType(Mouvement::TYPE_SORTIE);
    }

    // Les méthodes de Mouvement sont héritées automatiquement

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getMethodePaiement(): ?string
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(?string $methodePaiement): static
    {
        if ($methodePaiement && !in_array($methodePaiement, array_keys(self::METHODES))) {
            throw new \InvalidArgumentException(sprintf('Méthode de paiement invalide: %s', $methodePaiement));
        }

        $this->methodePaiement = $methodePaiement;

        return $this;
    }

    public function getMethodePaiementLabel(): ?string
    {
        return $this->methodePaiement ? self::METHODES[$this->methodePaiement] : null;
    }

    public function getRecu(): ?string
    {
        return $this->recu;
    }

    public function setRecu(?string $recu): static
    {
        $this->recu = $recu;

        return $this;
    }
}
