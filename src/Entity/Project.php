<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['slug'],
    message: 'project.slug.already_exists',
)]
class Project
{
    private const VALID_TYPES      = ['film', 'serie', 'jeu_video', 'custom'];
    private const VALID_STATUSES   = ['draft', 'in_progress', 'completed', 'archived'];
    private const VALID_MODERATION = ['clear', 'warned', 'suspended'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ─── Propriétés avec Hooks (PHP 8.4) ──────────────────────────────────

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'project.title.not_blank')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'project.title.too_short',
        maxMessage: 'project.title.too_long',
    )]
    public string $title = '' {
        set => trim((string) $value);
    }

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: 'project.slug.invalid_format',
    )]
    public string $slug = '' {
        set => $this->slug = strtolower(trim((string) $value));
    }

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'project.description.too_long',
    )]
    public string $description = '' {
        set => $this->description = trim((string) $value);
    }

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['film', 'serie', 'jeu_video', 'custom'],
        message: 'project.type.invalid',
    )]
    public string $projectType = 'custom' {
        set {
            if (!in_array($value, self::VALID_TYPES, true)) {
                throw new \InvalidArgumentException("Type invalide : $value");
            }
            $this->projectType = $value;
        }
    }

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['draft', 'in_progress', 'completed', 'archived'],
        message: 'project.status.invalid',
    )]
    public string $status = 'draft' {
        set {
            if (!in_array($value, self::VALID_STATUSES, true)) {
                throw new \InvalidArgumentException("Statut invalide : $value");
            }
            $this->status = $value;
        }
    }

    #[ORM\Column(length: 50)]
    public string $moderationStatus = 'clear' {
        set {
            if (!in_array($value, self::VALID_MODERATION, true)) {
                throw new \InvalidArgumentException("Modération invalide : $value");
            }
            $this->moderationStatus = $value;
        }
    }

    #[ORM\Column]
    public bool $isPublic = false;

    #[ORM\Column]
    public int $reportCount = 0;

    #[ORM\Column(type: 'jsonb')]
    public array $customStructure = [];

    #[ORM\Column(type: 'jsonb')]
    public array $settings = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverFilename = null;

    // ─── Relations ──────────────────────────────────────────────────────────

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $createdBy = null;

    #[ORM\OneToMany(targetEntity: ProjectFeature::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $projectFeatures;

    #[ORM\OneToMany(targetEntity: ScenarioElement::class, mappedBy: 'project', cascade: ['remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $scenarioElements;

    #[ORM\OneToMany(targetEntity: Character::class, mappedBy: 'project', cascade: ['remove'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $characters;

    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'project', cascade: ['remove'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $locations;

    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'project', cascade: ['remove'])]
    private Collection $tags;

    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $projectMembers;
    #[ORM\OneToMany(targetEntity: ProjectTypeConfig::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $projectTypeConfigs;
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    // ─── Constructeur ───────────────────────────────────────────────────────

    public function __construct()
    {
        $this->projectFeatures  = new ArrayCollection();
        $this->scenarioElements = new ArrayCollection();
        $this->characters       = new ArrayCollection();
        $this->locations        = new ArrayCollection();
        $this->tags             = new ArrayCollection();
        $this->projectMembers   = new ArrayCollection();
        $this->createdAt        = new \DateTimeImmutable();
        $this->updatedAt        = new \DateTimeImmutable();
        $this->projectTypeConfigs = new ArrayCollection();
    }

    // ─── Lifecycle Callbacks ────────────────────────────────────────────────

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        // Génération automatique du slug si vide
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->title);
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Getters (requis pour Twig, Forms, ProjectAccessTrait) ─────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getProjectType(): string
    {
        return $this->projectType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getModerationStatus(): string
    {
        return $this->moderationStatus;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getReportCount(): int
    {
        return $this->reportCount;
    }

    public function getCustomStructure(): array
    {
        return $this->customStructure;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getCoverFilename(): ?string
    {
        return $this->coverFilename;
    }

    // Alias pour faciliter l'accès dans Twig
    public function getOwner(): ?User
    {
        return $this->getCreatedBy();
    }
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, ProjectFeature>
     */
    public function getProjectFeatures(): Collection
    {
        return $this->projectFeatures;
    }

    /**
     * @return Collection<int, ScenarioElement>
     */
    public function getScenarioElements(): Collection
    {
        return $this->scenarioElements;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @return Collection<int, ProjectMember>
     */
    public function getProjectMembers(): Collection
    {
        return $this->projectMembers;
    }
    /**
     * @return Collection<int, ProjectTypeConfig>
     */
    public function getProjectTypeConfigs(): Collection
    {
        return $this->projectTypeConfigs;
    }

    // ─── Setters ────────────────────────────────────────────────────────────

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setCreatedBy(?User $user): self
    {
        $this->createdBy = $user;
        return $this;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function setCoverFilename(?string $filename): self
    {
        $this->coverFilename = $filename;
        return $this;
    }

    public function setModerationStatus(string $status): self
    {
        $this->moderationStatus = $status;
        return $this;
    }

    public function incrementReportCount(): self
    {
        $this->reportCount++;
        return $this;
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Génère un slug unique à partir d'un titre.
     * Format : {slug-base}-{8-chars-alphanumeric}
     * Exemple : "Les Ombres de Bruxelles" → "les-ombres-de-bruxelles-k9m2p8x4"
     */
    private function generateSlug(string $title): string
    {
        // Normalisation : minuscules, accents virés, espaces → tirets
        $slug = strtolower(trim($title));
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Suffixe random 8 caractères (alphanumeric)
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $suffix = '';
        for ($i = 0; $i < 8; $i++) {
            $suffix .= $chars[random_int(0, 35)];
        }

        return $slug . '-' . $suffix;
    }

    /**
     * Vérifie si l'utilisateur donné est le propriétaire du projet.
     */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->createdBy?->getId() === $user->getId();
    }
}
