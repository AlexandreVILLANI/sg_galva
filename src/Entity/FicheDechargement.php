<?php

namespace App\Entity;

use App\Repository\FicheDechargementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FicheDechargementRepository::class)]
class FicheDechargement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column]
    private ?int $totalPaquets = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $cariste = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\OneToMany(mappedBy: 'fiche', targetEntity: LigneDechargement::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $lignes;

    #[ORM\OneToMany(mappedBy: 'fiche', targetEntity: PhotoDechargement::class, orphanRemoval: true)]
    private Collection $photos;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    // Getters et Setters... (Je les omets pour la clarté, mais ils sont générés par Symfony)
    public function getId(): ?int { return $this->id; }
    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): self { $this->date = $date; return $this; }
    public function getObservations(): ?string { return $this->observations; }
    public function setObservations(?string $observations): self { $this->observations = $observations; return $this; }
    public function getTotalPaquets(): ?int { return $this->totalPaquets; }
    public function setTotalPaquets(int $totalPaquets): self { $this->totalPaquets = $totalPaquets; return $this; }
    public function getCariste(): ?User { return $this->cariste; }
    public function setCariste(?User $cariste): self { $this->cariste = $cariste; return $this; }
    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): self { $this->client = $client; return $this; }
    public function getLignes(): Collection { return $this->lignes; }
    public function addLigne(LigneDechargement $ligne): self { if (!$this->lignes->contains($ligne)) { $this->lignes->add($ligne); $ligne->setFiche($this); } return $this; }
}