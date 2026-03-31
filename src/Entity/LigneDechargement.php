<?php

namespace App\Entity;

use App\Repository\LigneDechargementRepository;
use Doctrine\DBAL\Types\Types; 
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Emplacement $emplacement = null;

    #[ORM\ManyToOne(inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FicheDechargement $fiche = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $poids = null; // Poids en KG

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $u = null; // "U" (contient des lettres)

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference = null; // Référence (ex: GK, GFO...)

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $travauxAnnexes = null; // Liste des travaux à faire

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null; // Observations générales

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $prixTonne = null;

    // --- GETTERS ET SETTERS ---

    public function getId(): ?int { return $this->id; }

    public function getNbPaquets(): ?int { return $this->nbPaquets; }
    public function setNbPaquets(int $nbPaquets): self { $this->nbPaquets = $nbPaquets; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getEmplacement(): ?Emplacement { return $this->emplacement; }
    public function setEmplacement(?Emplacement $emplacement): self { $this->emplacement = $emplacement; return $this; }

    public function getFiche(): ?FicheDechargement { return $this->fiche; }
    public function setFiche(?FicheDechargement $fiche): self { $this->fiche = $fiche; return $this; }

    public function getPoids(): ?float { return $this->poids; }
    public function setPoids(?float $poids): self { $this->poids = $poids; return $this; }

    public function getU(): ?string { return $this->u; }
    public function setU(?string $u): self { $this->u = $u; return $this; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): self { $this->reference = $reference; return $this; }

    public function getTravauxAnnexes(): ?string { return $this->travauxAnnexes; }
    public function setTravauxAnnexes(?string $travauxAnnexes): self { $this->travauxAnnexes = $travauxAnnexes; return $this; }

    public function getObservations(): ?string { return $this->observations; }
    public function setObservations(?string $observations): self { $this->observations = $observations; return $this; }

    public function getPrixTonne(): ?float { return $this->prixTonne; }
    public function setPrixTonne(?float $prixTonne): self { $this->prixTonne = $prixTonne; return $this; }
}