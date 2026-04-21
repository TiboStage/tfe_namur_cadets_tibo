<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
#[ORM\HasLifecycleCallbacks]
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
    #[Assert\NotBlank]
    public string $title = '' {
        set => $this->title = trim((string)$value);
    }

    #[ORM\Column(length: 255, unique: true)]
    public string $slug = '';

    #[ORM\Column(type: Types::TEXT)]
    public string $description = '' {
        set => $this->description = trim((string)$value);
    }

    #[ORM\Column(length: 50)]
    public string $projectType = 'custom' {
        set {
            if (!in_array($value, self::VALID_TYPES)) throw new \InvalidArgumentException("Type invalide");
            $this->projectType = $value;
        }
    }

    #[ORM\Column(length: 50)]
    public string $status = 'draft' {
        set {
            if (!in_array($value, self::VALID_STATUSES)) throw new \InvalidArgumentException("Statut invalide");
            $this->status = $value;
        }
    }

    #[ORM\Column(length: 50)]
    public string $moderationStatus = 'clear' {
        set {
            if (!in_array($value, self::VALID_MODERATION)) throw new \InvalidArgumentException("Modération invalide");
            $this->moderationStatus = $value;
        }
    }

    #[ORM\Column]
    public bool $isPublic = false;

    #[ORM\Column]
    public int $reportCount = 0;

    #[ORM\Column(type: Types::JSON)]
    public array $customStructure = [];

    #[ORM\Column(type: Types::JSON)]
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
    private Collection $characters;

    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'project', cascade: ['remove'])]
    private Collection $locations;

    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'project', cascade: ['remove'])]
    private Collection $tags;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist', 'remove'])]
    private Collection $projectMembers;

    // ─── Logique et Compatibilité ───────────────────────────────────────────

    public function __construct()
    {
        $this->projectFeatures  = new ArrayCollection();
        $this->scenarioElements = new ArrayCollection();
        $this->characters       = new ArrayCollection();
        $this->locations        = new ArrayCollection();
        $this->tags             = new ArrayCollection();
        $this->createdAt        = new \DateTimeImmutable();
        $this->updatedAt        = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        if (empty($this->slug)) {
            $slugger = new AsciiSlugger();
            $baseSlug = strtolower($slugger->slug($this->title));
            try {
                $this->slug = $baseSlug . '-' . bin2hex(random_bytes(3));
            } catch (\Exception) {
                $this->slug = $baseSlug . '-' . uniqid();
            }
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Getters indispensables (Avec twig et les traits) ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function getScenarioElements(): Collection
    {
        return $this->scenarioElements;
    }

    // --- Setters ---
    public function setCreatedBy(?User $user): self
    {
        $this->createdBy = $user;
        return $this;
    }

    public function setIsPublic(bool $v): self
    {
        $this->isPublic = $v;
        return $this;
    }

    public function setSlug(string $v): self
    {
        $this->slug = $v;
        return $this;
    }
}
