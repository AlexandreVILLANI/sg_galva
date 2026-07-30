<?php

namespace App\Entity;

use App\Repository\DechargementUrgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DechargementUrgenceRepository::class)]
class DechargementUrgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(targetEntity: Client::class)]
    private ?Client $client = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\OneToMany(mappedBy: 'dechargementUrgence', targetEntity: LigneDechargementUrgence::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $lignes;

    #[ORM\OneToMany(mappedBy: 'dechargementUrgence', targetEntity: PhotoUrgence::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $photos;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->dateCreation = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * @return Collection<int, LigneDechargementUrgence>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneDechargementUrgence $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setDechargementUrgence($this);
        }

        return $this;
    }

    public function removeLigne(LigneDechargementUrgence $ligne): static
    {
        if ($this->lignes->removeElement($ligne)) {
            // set the owning side to null (unless already changed)
            if ($ligne->getDechargementUrgence() === $this) {
                $ligne->setDechargementUrgence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PhotoUrgence>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(PhotoUrgence $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setDechargementUrgence($this);
        }

        return $this;
    }

    public function removePhoto(PhotoUrgence $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getDechargementUrgence() === $this) {
                $photo->setDechargementUrgence(null);
            }
        }

        return $this;
    }
}
