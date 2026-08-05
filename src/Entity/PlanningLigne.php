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
    #[ORM\ManyToOne(targetEntity: BonTravail::class, inversedBy: 'planningLignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonTravail $bonTravail = null;

    /**
     * Heure de passage / Mise à disposition (Saisie manuelle)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
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
     * Notes particulières pour cette planification précise (Ordonnancement)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observations = null;

    /**
     * Marqueur de priorité pour l'atelier (Important = vrai/faux)
     */
    #[ORM\Column(type: 'boolean')]
    private ?bool $importance = false;

    // =========================================================================
    // SUIVI DE LA VALIDATION (Chef d'équipe)
    // =========================================================================

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validePar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaireAtelier = null;

    #[ORM\Column(nullable: true)]
    private ?int $ordre = null;

    // =========================================================================
    // CONTRÔLE QUALITÉ & PRODUCTION (Formulaire Chef d'Équipe)
    // =========================================================================

    // --- 1. Contrôle Qualité ---
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $qualiteConforme = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $qualiteFicheNC = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $qualiteOperations = null;

    // --- 2. Contrôle Affichage ---
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $affichageCaseCE = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $affichageCaseControleur = null;

    // --- 3. Processus Bains ---
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $traitementSurfaceConforme = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $bainZincConforme = null;

    // --- 4. Rebuts ---
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rebuts = null;

    // --- 5. Contrôle Après Traitement ---
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $finalConforme = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $finalFicheNC = null;

    // =========================================================================
    // GETTERS ET SETTERS
    // =========================================================================

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

    public function isImportance(): ?bool
    {
        return $this->importance;
    }

    public function setImportance(bool $importance): self
    {
        $this->importance = $importance;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function setValidePar(?User $validePar): self
    {
        $this->validePar = $validePar;
        return $this;
    }

    public function getCommentaireAtelier(): ?string
    {
        return $this->commentaireAtelier;
    }

    public function setCommentaireAtelier(?string $commentaireAtelier): self
    {
        $this->commentaireAtelier = $commentaireAtelier;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }

    // --- NOUVEAUX GETTERS/SETTERS QUALITÉ ---

    public function isQualiteConforme(): ?bool
    {
        return $this->qualiteConforme;
    }

    public function setQualiteConforme(?bool $qualiteConforme): self
    {
        $this->qualiteConforme = $qualiteConforme;
        return $this;
    }

    public function getQualiteFicheNC(): ?string
    {
        return $this->qualiteFicheNC;
    }

    public function setQualiteFicheNC(?string $qualiteFicheNC): self
    {
        $this->qualiteFicheNC = $qualiteFicheNC;
        return $this;
    }

    public function getQualiteOperations(): ?string
    {
        return $this->qualiteOperations;
    }

    public function setQualiteOperations(?string $qualiteOperations): self
    {
        $this->qualiteOperations = $qualiteOperations;
        return $this;
    }

    public function getAffichageCaseCE(): ?string
    {
        return $this->affichageCaseCE;
    }

    public function setAffichageCaseCE(?string $affichageCaseCE): self
    {
        $this->affichageCaseCE = $affichageCaseCE;
        return $this;
    }

    public function getAffichageCaseControleur(): ?string
    {
        return $this->affichageCaseControleur;
    }

    public function setAffichageCaseControleur(?string $affichageCaseControleur): self
    {
        $this->affichageCaseControleur = $affichageCaseControleur;
        return $this;
    }

    public function isTraitementSurfaceConforme(): ?bool
    {
        return $this->traitementSurfaceConforme;
    }

    public function setTraitementSurfaceConforme(?bool $traitementSurfaceConforme): self
    {
        $this->traitementSurfaceConforme = $traitementSurfaceConforme;
        return $this;
    }

    public function isBainZincConforme(): ?bool
    {
        return $this->bainZincConforme;
    }

    public function setBainZincConforme(?bool $bainZincConforme): self
    {
        $this->bainZincConforme = $bainZincConforme;
        return $this;
    }

    public function getRebuts(): ?string
    {
        return $this->rebuts;
    }

    public function setRebuts(?string $rebuts): self
    {
        $this->rebuts = $rebuts;
        return $this;
    }

    public function isFinalConforme(): ?bool
    {
        return $this->finalConforme;
    }

    public function setFinalConforme(?bool $finalConforme): self
    {
        $this->finalConforme = $finalConforme;
        return $this;
    }

    public function getFinalFicheNC(): ?string
    {
        return $this->finalFicheNC;
    }

    public function setFinalFicheNC(?string $finalFicheNC): self
    {
        $this->finalFicheNC = $finalFicheNC;
        return $this;
    }
}