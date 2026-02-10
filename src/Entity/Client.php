<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\DBAL\Types\Types; 
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null; // Correspond à la colonne "Intitulé"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse_livraison = null; 

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse_facturation = null; // Correspond à "Adresse"

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codePostal = null; // Correspond à "Code postal"

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $ville = null; // Correspond à "Ville"

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null; // Correspond à "Téléphone"

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $fax = null; // Correspond à "Télécopie"


    // --- 2. LES NOUVEAUX CHAMPS (Pour stocker le reste de l'Excel) ---

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $refInterne = null; // Correspond à "Numéro" (ex: 00933207)

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $abrege = null; // Correspond à "Abrégé"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null; // Correspond à "Contact"

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $siret = null; // Correspond à "N° Siret"

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pays = null; // Correspond à "Pays"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null; // Correspond à "E-mail"

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tvaIntra = null; // Correspond à "N° identifiant"

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $encoursAutorise = null; // Correspond à "Encours autorisé"

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $messageAlerte = null; // Correspond à "Message Alerte"

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $categorieComptable = null; // Correspond à "Catégorie comptable"

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getAdresseFacturation(): ?string { return $this->adresse_facturation; }
    public function setAdresseFacturation(?string $adresse_facturation): static { $this->adresse_facturation = $adresse_facturation; return $this; }

    public function getAdresseLivraison(): ?string { return $this->adresse_livraison; }
    public function setAdresseLivraison(?string $adresse_livraison): static { $this->adresse_livraison = $adresse_livraison; return $this; }

    public function getCodePostal(): ?string { return $this->codePostal; }
    public function setCodePostal(?string $codePostal): static { $this->codePostal = $codePostal; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $ville): static { $this->ville = $ville; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getFax(): ?string { return $this->fax; }
    public function setFax(?string $fax): static { $this->fax = $fax; return $this; }

    public function getRefInterne(): ?string { return $this->refInterne; }
    public function setRefInterne(?string $refInterne): static { $this->refInterne = $refInterne; return $this; }

    public function getAbrege(): ?string { return $this->abrege; }
    public function setAbrege(?string $abrege): static { $this->abrege = $abrege; return $this; }

    public function getContact(): ?string { return $this->contact; }
    public function setContact(?string $contact): static { $this->contact = $contact; return $this; }

    public function getSiret(): ?string { return $this->siret; }
    public function setSiret(?string $siret): static { $this->siret = $siret; return $this; }

    public function getPays(): ?string { return $this->pays; }
    public function setPays(?string $pays): static { $this->pays = $pays; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTvaIntra(): ?string { return $this->tvaIntra; }
    public function setTvaIntra(?string $tvaIntra): static { $this->tvaIntra = $tvaIntra; return $this; }

    public function getEncoursAutorise(): ?string { return $this->encoursAutorise; }
    public function setEncoursAutorise(?string $encoursAutorise): static { $this->encoursAutorise = $encoursAutorise; return $this; }

    public function getMessageAlerte(): ?string { return $this->messageAlerte; }
    public function setMessageAlerte(?string $messageAlerte): static { $this->messageAlerte = $messageAlerte; return $this; }

    public function getCategorieComptable(): ?string { return $this->categorieComptable; }
    public function setCategorieComptable(?string $categorieComptable): static { $this->categorieComptable = $categorieComptable; return $this; }
}