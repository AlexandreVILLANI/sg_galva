<?php

namespace App\Entity;

use App\Repository\LigneDechargementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneDechargementRepository::class)]
class LigneDechargement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $nbPaquets = null;

    // Nouveau champ pour le nom du produit / description
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Emplacement $emplacement = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FicheDechargement $fiche = null;

    public function getId(): ?int { return $this->id; }

    public function getNbPaquets(): ?int { return $this->nbPaquets; }
    public function setNbPaquets(int $nbPaquets): self { $this->nbPaquets = $nbPaquets; return $this; }

    // Getter et Setter pour la description
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getEmplacement(): ?Emplacement { return $this->emplacement; }
    public function setEmplacement(?Emplacement $emplacement): self { $this->emplacement = $emplacement; return $this; }

    public function getFiche(): ?FicheDechargement { return $this->fiche; }
    public function setFiche(?FicheDechargement $fiche): self { $this->fiche = $fiche; return $this; }
}