<?php

namespace App\Entity;

use App\Repository\ScenarioElementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Élément narratif hiérarchique (auto-référencé via parent_id).
 *
 * Type : 'act' | 'sequence' | 'scene' | 'episode' | 'chapter' | etc.
 * depth : dénormalisé — évite les jointures récursives.
 *
 * Contenu JSONB (blocs) :
 * [
 *   {"type": "slug",   "content": "INT. APPARTEMENT - JOUR"},
 *   {"type": "action", "content": "Marie entre dans la pièce."},
 *   {"type": "char",   "content": "MARIE"},
 *   {"type": "diag",   "content": "Tu as vu ce qu'il a fait ?"}
 * ]
 */
#[ORM\Entity(repositoryClass: ScenarioElementRepository::class)]
#[ORM\Table(name: 'scenario_element')]
#[ORM\HasLifecycleCallbacks]
class ScenarioElement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'scenarioElements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, ScenarioElement> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $children;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'scenarioElements')]
    #[ORM\JoinTable(name: 'scenario_element_tag')]
    private Collection $tags;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $elementType = '' {
        get => $this->elementType;
        set => $this->elementType = trim($value);
    }

    /**
     * Niveau dans la hiérarchie (dénormalisé).
     * 1 = racine (Acte), 2 = enfant (Séquence), etc.
     */
    #[ORM\Column(type: 'integer')]
    public int $depth = 1 {
        get => $this->depth;
        set {
            if ($value < 1) {
                throw new \InvalidArgumentException("La profondeur doit être >= 1");
            }
            $this->depth = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $title = '' {
        get => $this->title;
        set => $this->title = trim($value);
    }

    #[ORM\Column(type: 'json')]
    public array $content = [];

    #[ORM\Column(type: 'text')]
    public string $summary = '' {
        get => $this->summary;
        set => $this->summary = trim($value);
    }

    #[ORM\Column(type: 'integer')]
    public int $orderIndex = 0;

    #[ORM\Column(type: 'integer')]
    public int $durationSeconds = 0 {
        get => $this->durationSeconds;
        set {
            if ($value < 0) {
                throw new \InvalidArgumentException("La durée ne peut pas être négative");
            }
            $this->durationSeconds = $value;
        }
    }

    #[ORM\Column(type: 'json')]
    public array $metadata = [];

    // ─── Timestamps ───────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    // ─── Constructeur ─────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->children  = new ArrayCollection();
        $this->tags      = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Getters read-only ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }

    /** @return Collection<int, ScenarioElement> */
    public function getChildren(): Collection { return $this->children; }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }
        return $this;
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection { return $this->tags; }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    // ─── Setters compat Symfony Forms ────────────────────────────────────────

    public function setElementType(string $v): static { $this->elementType = trim($v); return $this; }
    public function setDepth(int $v): static { $this->depth = $v; return $this; }
    public function setTitle(string $v): static { $this->title = trim($v); return $this; }
    public function setContent(array $v): static { $this->content = $v; return $this; }
    public function setSummary(string $v): static { $this->summary = trim($v); return $this; }
    public function setOrderIndex(int $v): static { $this->orderIndex = $v; return $this; }
    public function setDurationSeconds(int $v): static { $this->durationSeconds = $v; return $this; }
    public function setMetadata(array $v): static { $this->metadata = $v; return $this; }

    // ─── Getters compat Twig/code existant ───────────────────────────────────

    public function getElementType(): string { return $this->elementType; }
    public function getDepth(): int { return $this->depth; }
    public function getTitle(): string { return $this->title; }
    public function getContent(): array { return $this->content; }
    public function getSummary(): string { return $this->summary; }
    public function getOrderIndex(): int { return $this->orderIndex; }
    public function getDurationSeconds(): int { return $this->durationSeconds; }
    public function getMetadata(): array { return $this->metadata; }
}
