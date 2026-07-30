<?php

namespace App\Entity;

use App\Repository\LigneDechargementUrgenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneDechargementUrgenceRepository::class)]
class LigneDechargementUrgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbPalettes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomProduit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poids = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bac = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DechargementUrgence $dechargementUrgence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbPalettes(): ?int
    {
        return $this->nbPalettes;
    }

    public function setNbPalettes(?int $nbPalettes): static
    {
        $this->nbPalettes = $nbPalettes;
        return $this;
    }

    public function getNomProduit(): ?string
    {
        return $this->nomProduit;
    }

    public function setNomProduit(?string $nomProduit): static
    {
        $this->nomProduit = $nomProduit;
        return $this;
    }

    public function getPoids(): ?string
    {
        return $this->poids;
    }

    public function setPoids(?string $poids): static
    {
        $this->poids = $poids;
        return $this;
    }

    public function getBac(): ?string
    {
        return $this->bac;
    }

    public function setBac(?string $bac): static
    {
        $this->bac = $bac;
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
