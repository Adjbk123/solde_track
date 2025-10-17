<?php

namespace App\Entity;

use App\Repository\DonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonRepository::class)]
#[ORM\Table(name: 'don')]
class Don extends Mouvement
{
    // L'ID est hérité de Mouvement, pas besoin de le redéfinir

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occasion = null;

    public function __construct()
    {
        parent::__construct();
        $this->setType(Mouvement::TYPE_DON);
        $this->setStatut(\App\Entity\StatutsMouvement::STATUT_DON_EFFECTUE);
    }

    // Les méthodes de Mouvement sont héritées automatiquement

    public function getOccasion(): ?string
    {
        return $this->occasion;
    }

    public function setOccasion(?string $occasion): static
    {
        $this->occasion = $occasion;

        return $this;
    }
}
