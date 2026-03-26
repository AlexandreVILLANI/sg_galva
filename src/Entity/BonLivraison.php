<?php

namespace App\Entity;

use App\Repository\BonLivraisonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BonLivraisonRepository::class)]
class BonLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // --- RELATION : 1 Bon de Livraison = 1 Bon de Travail ---
    #[ORM\OneToOne(targetEntity: BonTravail::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonTravail $bonTravail = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $numero = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnlevement = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $transporteur = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $chauffeur = null;

    // Type TEXT pour stocker l'image de la signature en Base64 ou le chemin du fichier
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $signature = null;

    public function __construct()
    {
        // La date de création se remplit toute seule à l'instant T
        $this->dateCreation = new \DateTime();
    }

    // =========================================================================
    // GETTERS ET SETTERS
    // =========================================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBonTravail(): ?BonTravail
    {
        return $this->bonTravail;
    }

    public function setBonTravail(BonTravail $bonTravail): self
    {
        $this->bonTravail = $bonTravail;
        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateEnlevement(): ?\DateTimeInterface
    {
        return $this->dateEnlevement;
    }

    public function setDateEnlevement(?\DateTimeInterface $dateEnlevement): self
    {
        $this->dateEnlevement = $dateEnlevement;
        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    public function getTransporteur(): ?string
    {
        return $this->transporteur;
    }

    public function setTransporteur(?string $transporteur): self
    {
        $this->transporteur = $transporteur;
        return $this;
    }

    public function getChauffeur(): ?string
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?string $chauffeur): self
    {
        $this->chauffeur = $chauffeur;
        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): self
    {
        $this->signature = $signature;
        return $this;
    }
}