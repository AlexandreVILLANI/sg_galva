<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BonTravailRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: BonTravailRepository::class)]
class BonTravail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'bonTravail', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonDeCommande $bonCommande = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    #[ORM\Column(length: 50, unique: true)] 
    private ?string $numero = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $delaiClient = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $exigenceParticuliere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $repriseUsinage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isPeseeValidee = false;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isTermine = false; // Ajouté car il manquait pour ton getter isTermine()

    #[ORM\OneToMany(mappedBy: 'bonTravail', targetEntity: PlanningLigne::class)]
    private Collection $planningLignes;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $demandeCertificat = false;

    // --- NOUVEAUX CHAMPS POUR LE FORFAIT ---
    
    #[ORM\Column(options: ["default" => false])]
    private ?bool $isForfait = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomForfait = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $prixForfait = null;

    #[ORM\Column(length: 10, options: ["default" => 'GALVA'])]
    private ?string $type = 'GALVA';

    // ---------------------------------------

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->isPeseeValidee = false; 
        $this->isTermine = false;
        $this->demandeCertificat = false;
        $this->isForfait = false; // Initialisation du forfait à false
        $this->type = 'GALVA';
        $this->planningLignes = new ArrayCollection(); 
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getId(): ?int { return $this->id; }

    public function getBonCommande(): ?BonDeCommande { return $this->bonCommande; }
    public function setBonCommande(BonDeCommande $bonCommande): self { $this->bonCommande = $bonCommande; return $this; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $numero): self { $this->numero = $numero; return $this; }

    public function getDelaiClient(): ?\DateTimeInterface { return $this->delaiClient; }
    public function setDelaiClient(?\DateTimeInterface $delaiClient): self { $this->delaiClient = $delaiClient; return $this; }

    public function getExigenceParticuliere(): ?string { return $this->exigenceParticuliere; }
    public function setExigenceParticuliere(?string $exigenceParticuliere): self { $this->exigenceParticuliere = $exigenceParticuliere; return $this; }

    public function getRepriseUsinage(): ?string { return $this->repriseUsinage; }
    public function setRepriseUsinage(?string $repriseUsinage): self { $this->repriseUsinage = $repriseUsinage; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }
    
    public function getLignes(): \Doctrine\Common\Collections\Collection
    {
        if ($this->bonCommande && $this->bonCommande->getFiche()) {
            return $this->bonCommande->getFiche()->getLignes();
        }
        return new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function addLigne($ligne): self { return $this; }
    public function removeLigne($ligne): self { return $this; }
    
    public function getObservations(): ?string{return $this->observations;}
    public function setObservations(?string $observations): self{$this->observations = $observations;return $this;}

    public function isTermine(): ?bool
    {
        return $this->isTermine;
    }

    public function setIsTermine(bool $isTermine): static
    {
        $this->isTermine = $isTermine;
        return $this;
    }

    public function isPeseeValidee(): ?bool
    {
        return $this->isPeseeValidee;
    }

    public function setIsPeseeValidee(bool $isPeseeValidee): self
    {
        $this->isPeseeValidee = $isPeseeValidee;
        return $this;
    }

    /**
     * @return Collection<int, PlanningLigne>
    */
    public function getPlanningLignes(): Collection
    {
        return $this->planningLignes;
    }

    public function isDemandeCertificat(): ?bool
    {
        return $this->demandeCertificat;
    }

    public function setDemandeCertificat(bool $demandeCertificat): self
    {
        $this->demandeCertificat = $demandeCertificat;
        return $this;
    }

    // --- GETTERS ET SETTERS POUR LE FORFAIT ---

    public function isForfait(): ?bool
    {
        return $this->isForfait;
    }

    public function setIsForfait(bool $isForfait): self
    {
        $this->isForfait = $isForfait;
        return $this;
    }

    public function getNomForfait(): ?string
    {
        return $this->nomForfait;
    }

    public function setNomForfait(?string $nomForfait): self
    {
        $this->nomForfait = $nomForfait;
        return $this;
    }

    public function getPrixForfait(): ?float
    {
        return $this->prixForfait;
    }

    public function setPrixForfait(?float $prixForfait): self
    {
        $this->prixForfait = $prixForfait;
        return $this;
    }
}