<?php

namespace App\Entity;

use App\Repository\BonDeCommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: BonDeCommandeRepository::class)]
class BonDeCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $refi = null; 

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $forfait = null; 

    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: FicheDechargement::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?FicheDechargement $fiche = null;

    #[ORM\OneToMany(mappedBy: 'bonDeCommande', targetEntity: PhotoBonCommande::class, cascade: ['persist', 'remove'])]
    private Collection $photos;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomChauffeur = null;

    #[ORM\Column(nullable: true)] 
    private ?bool $isGalvanisation = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isCataphorese = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $stockage = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeGalva = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    // --- CHAMPS DE VALIDATION ORDONNANCEMENT (DALI) ---
    // On garde ça car c'est utile pour filtrer la liste
    
    #[ORM\Column(options: ["default" => false])]
    private ?bool $isValidatedOrdo = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validatedAtOrdo = null;

    // -------------------------------------------

    public function __construct()
    {
        $this->date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->photos = new ArrayCollection();
        $this->isValidatedOrdo = false;
    }

    public function getId(): ?int { return $this->id; }

    public function getRefi(): ?string { return $this->refi; }
    public function setRefi(string $refi): static { $this->refi = $refi; return $this; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static {
        if ($date instanceof \DateTime) { $date->setTimezone(new \DateTimeZone('Europe/Paris')); }
        $this->date = $date;
        return $this;
    }

    public function getForfait(): ?string { return $this->forfait; }
    public function setForfait(?string $forfait): static { $this->forfait = $forfait; return $this; }

    public function getClient(): ?Client { return $this->client; }
    public function setClient(?Client $client): static { $this->client = $client; return $this; }

    public function getFiche(): ?FicheDechargement { return $this->fiche; }
    public function setFiche(?FicheDechargement $fiche): static { $this->fiche = $fiche; return $this; }

    public function getPhotos(): Collection { return $this->photos; }
    public function addPhoto(PhotoBonCommande $photo): static {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setBonDeCommande($this);
        }
        return $this;
    }
    public function removePhoto(PhotoBonCommande $photo): static {
        if ($this->photos->removeElement($photo)) {
            if ($photo->getBonDeCommande() === $this) { $photo->setBonDeCommande(null); }
        }
        return $this;
    }

    public function getNomChauffeur(): ?string { return $this->nomChauffeur; }
    public function setNomChauffeur(?string $nomChauffeur): static { $this->nomChauffeur = $nomChauffeur; return $this; }

    public function isIsGalvanisation(): ?bool { return $this->isGalvanisation; }
    public function setIsGalvanisation(?bool $isGalvanisation): static { $this->isGalvanisation = $isGalvanisation; return $this; }
    public function isGalvanisation(): ?bool { return $this->isGalvanisation; } // Alias

    public function isIsCataphorese(): ?bool { return $this->isCataphorese; }
    public function setIsCataphorese(?bool $isCataphorese): static { $this->isCataphorese = $isCataphorese; return $this; }
    public function isCataphorese(): ?bool { return $this->isCataphorese; } // Alias

    public function getStockage(): ?string { return $this->stockage; }
    public function setStockage(?string $stockage): static { $this->stockage = $stockage; return $this; }

    public function getTypeGalva(): ?string { return $this->typeGalva; }
    public function setTypeGalva(?string $typeGalva): static { $this->typeGalva = $typeGalva; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function isValidatedOrdo(): ?bool { return $this->isValidatedOrdo; }
    public function setIsValidatedOrdo(bool $isValidatedOrdo): static { $this->isValidatedOrdo = $isValidatedOrdo; return $this; }

    public function getValidatedAtOrdo(): ?\DateTimeInterface { return $this->validatedAtOrdo; }
    public function setValidatedAtOrdo(?\DateTimeInterface $validatedAtOrdo): static { $this->validatedAtOrdo = $validatedAtOrdo; return $this; }
}