<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fiche lieu d'un projet narratif.
 *
 * aliases (JSONB) : variantes de nom détectées dans l'éditeur.
 * Ex : ["Le manoir", "Le manoir des Blackwood", "la vieille bâtisse"]
 *
 * parent : hiérarchie des lieux (ex: Continent > Pays > Ville > Quartier)
 */
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
#[ORM\HasLifecycleCallbacks]
class Location
{
    private const VALID_TYPES = ['interior', 'exterior', 'fantasy', 'historical', 'futuristic'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    // ─── Hiérarchie ─────────────────────────────────────────────────────────

    /** Lieu parent (ex: une ville contient des quartiers) */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    /** Sous-lieux */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    // ─── Scalaires — property hooks PHP 8.4 ────────────────────────────────

    #[ORM\Column(type: 'string', length: 255)]
    public string $name = '' {
        get => $this->name;
        set => $this->name = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $description = '' {
        get => $this->description;
        set => $this->description = trim($value);
    }

    #[ORM\Column(type: 'string', length: 50)]
    public string $type = '' {
        get => $this->type;
        set {
            if ($value !== '' && !in_array($value, self::VALID_TYPES, strict: true)) {
                throw new \InvalidArgumentException("Type de lieu invalide : $value");
            }
            $this->type = $value;
        }
    }

    #[ORM\Column(type: 'json')]
    public array $aliases = [];

    #[ORM\Column(type: 'json')]
    public array $metadata = [];

    // ─── Nullable ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageFilename = null;

    // ─── Timestamps ─────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->children  = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Getters read-only ───────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // ─── Hiérarchie ─────────────────────────────────────────────────────────

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static
    {
        // Évite les boucles (un lieu ne peut pas être son propre parent)
        if ($parent === $this) {
            throw new \InvalidArgumentException('Un lieu ne peut pas être son propre parent.');
        }
        $this->parent = $parent;
        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection { return $this->children; }

    public function hasChildren(): bool { return !$this->children->isEmpty(); }

    /**
     * Retourne le chemin complet du lieu.
     * Ex: "Europe > France > Paris > Montmartre"
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $parts = [$this->name];
        $current = $this->parent;
        while ($current !== null) {
            array_unshift($parts, $current->getName());
            $current = $current->getParent();
        }
        return implode($separator, $parts);
    }

    // ─── Nullable ────────────────────────────────────────────────────────────

    public function getImageFilename(): ?string { return $this->imageFilename; }
    public function setImageFilename(?string $v): static { $this->imageFilename = $v; return $this; }

    // ─── Relation ────────────────────────────────────────────────────────────

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    // ─── Setters compat Symfony Forms ────────────────────────────────────────

    public function setName(string $v): static { $this->name = trim($v); return $this; }
    public function setDescription(string $v): static { $this->description = trim($v); return $this; }
    public function setType(string $v): static { $this->type = $v; return $this; }
    public function setAliases(array $v): static { $this->aliases = $v; return $this; }
    public function setMetadata(array $v): static { $this->metadata = $v; return $this; }

    // ─── Getters compat Twig ─────────────────────────────────────────────────

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getType(): string { return $this->type; }
    public function getAliases(): array { return $this->aliases; }
    public function getMetadata(): array { return $this->metadata; }
}
