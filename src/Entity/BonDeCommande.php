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

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->photos = new ArrayCollection(); 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefi(): ?string
    {
        return $this->refi;
    }

    public function setRefi(string $refi): static
    {
        $this->refi = $refi;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getForfait(): ?string
    {
        return $this->forfait;
    }

    public function setForfait(?string $forfait): static
    {
        $this->forfait = $forfait;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getFiche(): ?FicheDechargement
    {
        return $this->fiche;
    }

    public function setFiche(?FicheDechargement $fiche): static
    {
        $this->fiche = $fiche;
        return $this;
    }

    /** @return Collection<int, PhotoBonCommande> */
    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(PhotoBonCommande $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setBonDeCommande($this);
        }
        return $this;
    }
}