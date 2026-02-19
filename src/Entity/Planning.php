<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $datePlanning = null;

    #[ORM\Column(length: 10)] 
    private ?string $categorie = null; 

    #[ORM\OneToMany(mappedBy: 'planning', targetEntity: PlanningLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatePlanning(): ?\DateTimeInterface
    {
        return $this->datePlanning;
    }

    public function setDatePlanning(\DateTimeInterface $datePlanning): self
    {
        $this->datePlanning = $datePlanning;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    /**
     * @return Collection<int, PlanningLigne>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(PlanningLigne $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setPlanning($this);
        }
        return $this;
    }

    public function removeLigne(PlanningLigne $ligne): self
    {
        if ($this->lignes->removeElement($ligne)) {
            // set the owning side to null (unless already changed)
            if ($ligne->getPlanning() === $this) {
                $ligne->setPlanning(null);
            }
        }
        return $this;
    }

    /**
     * Calcule le nombre de commandes terminées (cases cochées)
     */
    public function getNbCdeDansLesTemps(): int
    {
        $count = 0;
        foreach ($this->lignes as $ligne) {
            if ($ligne->isAvancement() === true) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Calcule le nombre de commandes restant à faire (cases non cochées)
     */
    public function getNbCdeEnRetard(): int
    {
        $count = 0;
        foreach ($this->lignes as $ligne) {
            if ($ligne->isAvancement() === false) {
                $count++;
            }
        }
        return $count;
    }
}