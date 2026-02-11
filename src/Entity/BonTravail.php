<?php

namespace App\Entity;

use App\Repository\BonTravailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

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

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    // --- GETTERS ET SETTERS CORRIGÉS ---

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
}