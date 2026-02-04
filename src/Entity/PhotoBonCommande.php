<?php

namespace App\Entity;

use App\Repository\PhotoBonCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhotoBonCommandeRepository::class)]
class PhotoBonCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomFichier = null;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonDeCommande $bonDeCommande = null;

    public function getId(): ?int { return $this->id; }
    public function getNomFichier(): ?string { return $this->nomFichier; }
    public function setNomFichier(string $nomFichier): self { $this->nomFichier = $nomFichier; return $this; }
    
    public function getBonDeCommande(): ?BonDeCommande { return $this->bonDeCommande; }
    public function setBonDeCommande(?BonDeCommande $bonDeCommande): self { $this->bonDeCommande = $bonDeCommande; return $this; }
}