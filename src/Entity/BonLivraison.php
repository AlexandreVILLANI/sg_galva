<?php

namespace App\Entity;

use App\Repository\BonLivraisonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User; 
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: BonLivraisonRepository::class)]
class BonLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $signature = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $cariste = null;

    // =========================================================================
    // SÉPARATION DES VALIDATIONS
    // =========================================================================

    // Validation côté Cariste (Le chargement est fait)
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $caristeValide = false;

    // Validation côté Transporteur (Le document est signé)
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $signatureValide = false;

    #[ORM\OneToMany(mappedBy: 'bonLivraison', targetEntity: DocumentBonLivraison::class, cascade: ['persist', 'remove'])]
    private Collection $documents;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->documents = new ArrayCollection();
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

    public function getCariste(): ?User
    {
        return $this->cariste;
    }

    public function setCariste(?User $cariste): self
    {
        $this->cariste = $cariste;
        return $this;
    }

    public function isCaristeValide(): bool
    {
        return $this->caristeValide;
    }

    public function setCaristeValide(bool $caristeValide): self
    {
        $this->caristeValide = $caristeValide;
        return $this;
    }

    public function isSignatureValide(): bool
    {
        return $this->signatureValide;
    }

    public function setSignatureValide(bool $signatureValide): self
    {
        $this->signatureValide = $signatureValide;
        return $this;
    }

    /**
     * @return Collection<int, DocumentBonLivraison>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(DocumentBonLivraison $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setBonLivraison($this);
        }

        return $this;
    }

    public function removeDocument(DocumentBonLivraison $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getBonLivraison() === $this) {
                $document->setBonLivraison(null);
            }
        }

        return $this;
    }
}