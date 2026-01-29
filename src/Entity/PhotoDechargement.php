<?php

namespace App\Entity;

use App\Repository\PhotoDechargementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoDechargementRepository::class)]
class PhotoDechargement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomFichier = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FicheDechargement $fiche = null;

    public function getId(): ?int { return $this->id; }
    public function getNomFichier(): ?string { return $this->nomFichier; }
    public function setNomFichier(string $nomFichier): self { $this->nomFichier = $nomFichier; return $this; }
    public function getFiche(): ?FicheDechargement { return $this->fiche; }
    public function setFiche(?FicheDechargement $fiche): self { $this->fiche = $fiche; return $this; }
}