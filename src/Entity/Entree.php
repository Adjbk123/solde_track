<?php

namespace App\Entity;

use App\Repository\EntreeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntreeRepository::class)]
#[ORM\Table(name: 'entree')]
class Entree extends Mouvement
{
    public const METHODE_CASH = 'cash';
    public const METHODE_MOMO = 'momo';
    public const METHODE_VIREMENT = 'virement';
    public const METHODE_AUTRE = 'autre';

    public const METHODES = [
        self::METHODE_CASH => 'Cash',
        self::METHODE_MOMO => 'MoMo',
        self::METHODE_VIREMENT => 'Virement',
        self::METHODE_AUTRE => 'Autre',
    ];

    // L'ID est hérité de Mouvement, pas besoin de le redéfinir

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $methode = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType(Mouvement::TYPE_ENTREE);
        $this->setStatut(\App\Entity\StatutsMouvement::STATUT_ENTREE_CONFIRME);
    }

    // Les méthodes de Mouvement sont héritées automatiquement

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getMethode(): ?string
    {
        return $this->methode;
    }

    public function setMethode(?string $methode): static
    {
        if ($methode && !in_array($methode, array_keys(self::METHODES))) {
            throw new \InvalidArgumentException(sprintf('Méthode invalide: %s', $methode));
        }

        $this->methode = $methode;

        return $this;
    }

    public function getMethodeLabel(): ?string
    {
        return $this->methode ? self::METHODES[$this->methode] : null;
    }
}
