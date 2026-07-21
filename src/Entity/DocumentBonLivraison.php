<?php

namespace App\Entity;

use App\Repository\DocumentBonLivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentBonLivraisonRepository::class)]
class DocumentBonLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomFichier = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonLivraison $bonLivraison = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomFichier(): ?string
    {
        return $this->nomFichier;
    }

    public function setNomFichier(string $nomFichier): static
    {
        $this->nomFichier = $nomFichier;

        return $this;
    }

    public function getBonLivraison(): ?BonLivraison
    {
        return $this->bonLivraison;
    }

    public function setBonLivraison(?BonLivraison $bonLivraison): static
    {
        $this->bonLivraison = $bonLivraison;

        return $this;
    }
}
