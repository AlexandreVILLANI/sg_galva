<?php

namespace App\Entity;

use App\Repository\PlanningLigneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningLigneRepository::class)]
class PlanningLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Lien vers l'entête du planning (Date, Catégorie GB/PB)
     */
    #[ORM\ManyToOne(targetEntity: Planning::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Planning $planning = null;

    /**
     * Lien vers le Bon de Travail (permet d'accéder au REFI et au Client)
     */
    #[ORM\ManyToOne(targetEntity: BonTravail::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonTravail $bonTravail = null;

    /**
     * Heure de passage / Mise à disposition (Saisie manuelle)
     */
    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureMiseADisposition = null;

    /**
     * État de la ligne : Coché (Fait) ou Non coché (À faire)
     */
    #[ORM\Column(type: 'boolean')]
    private ?bool $avancement = false;

    /**
     * Code d'avancement sous forme de lettres (ex: G, P, L)
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $avancementCode = null;

    /**
     * Notes particulières pour cette planification précise
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    // --- GETTERS ET SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlanning(): ?Planning
    {
        return $this->planning;
    }

    public function setPlanning(?Planning $planning): self
    {
        $this->planning = $planning;
        return $this;
    }

    public function getBonTravail(): ?BonTravail
    {
        return $this->bonTravail;
    }

    public function setBonTravail(?BonTravail $bonTravail): self
    {
        $this->bonTravail = $bonTravail;
        return $this;
    }

    public function getHeureMiseADisposition(): ?\DateTimeInterface
    {
        return $this->heureMiseADisposition;
    }

    public function setHeureMiseADisposition(?\DateTimeInterface $heureMiseADisposition): self
    {
        $this->heureMiseADisposition = $heureMiseADisposition;
        return $this;
    }

    public function isAvancement(): ?bool
    {
        return $this->avancement;
    }

    public function setAvancement(bool $avancement): self
    {
        $this->avancement = $avancement;
        return $this;
    }

    public function getAvancementCode(): ?string
    {
        return $this->avancementCode;
    }

    public function setAvancementCode(?string $avancementCode): self
    {
        $this->avancementCode = $avancementCode;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }
}