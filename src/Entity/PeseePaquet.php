<?php

namespace App\Entity;

use App\Repository\PeseePaquetRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: PeseePaquetRepository::class)]
class PeseePaquet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'peseePaquets')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?LigneDechargement $ligneDechargement = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $poids = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLigneDechargement(): ?LigneDechargement
    {
        return $this->ligneDechargement;
    }

    public function setLigneDechargement(?LigneDechargement $ligneDechargement): self
    {
        $this->ligneDechargement = $ligneDechargement;

        return $this;
    }

    public function getPoids(): ?float
    {
        return $this->poids;
    }

    public function setPoids(?float $poids): self
    {
        $this->poids = $poids;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;

        return $this;
    }
}
