<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tag coloré pour catégoriser les éléments narratifs.
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tag')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    /** @var Collection<int, ScenarioElement> */
    #[ORM\ManyToMany(targetEntity: ScenarioElement::class, mappedBy: 'tags')]
    private Collection $scenarioElements;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 100)]
    public string $name = '' {
        get => $this->name;
        set => $this->name = trim($value);
    }

    /**
     * Couleur hexadécimale (ex : '#3B82F6').
     * Validée via hook — doit correspondre au format #RRGGBB.
     */
    #[ORM\Column(type: 'string', length: 7)]
    public string $color = '#6B7280' {
        get => $this->color;
        set {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                throw new \InvalidArgumentException("Couleur invalide : $value. Format attendu : #RRGGBB");
            }
            $this->color = strtoupper($value);
        }
    }

    // ─── Timestamps ───────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // ─── Constructeur ─────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->scenarioElements = new ArrayCollection();
        $this->createdAt        = new \DateTimeImmutable();
    }

    // ─── Getters read-only ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ─── Relation ────────────────────────────────────────────────────────────

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    /** @return Collection<int, ScenarioElement> */
    public function getScenarioElements(): Collection { return $this->scenarioElements; }

    // ─── Setters compat Symfony Forms ────────────────────────────────────────

    public function setName(string $v): static { $this->name = trim($v); return $this; }
    public function setColor(string $v): static { $this->color = $v; return $this; }

    // ─── Getters compat Twig/code existant ───────────────────────────────────

    public function getName(): string { return $this->name; }
    public function getColor(): string { return $this->color; }
}
