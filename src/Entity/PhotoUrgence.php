<?php

namespace App\Entity;

use App\Repository\PhotoUrgenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoUrgenceRepository::class)]
class PhotoUrgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomFichier = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DechargementUrgence $dechargementUrgence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomFichier(): ?string
    {
        return $this->nomFichier;
    }

    public function setNomFichier(string $nomFichier): static
    {
        $this->nomFichier = $nomFichier;
        return $this;
    }

    public function getDechargementUrgence(): ?DechargementUrgence
    {
        return $this->dechargementUrgence;
    }

    public function setDechargementUrgence(?DechargementUrgence $dechargementUrgence): static
    {
        $this->dechargementUrgence = $dechargementUrgence;
        return $this;
    }
}
