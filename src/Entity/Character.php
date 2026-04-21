<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fiche personnage d'un projet narratif.
 *
 * aliases (JSONB) : variantes de nom détectées dans l'éditeur.
 * Ex : ["Marie", "Marie Dupont", "la détective"]
 */
#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '"character"')]
#[ORM\HasLifecycleCallbacks]
class Character
{
    private const VALID_ROLES = ['protagonist', 'antagonist', 'secondary', 'figurant'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    /** @var Collection<int, CharacterRelation> */
    #[ORM\OneToMany(targetEntity: CharacterRelation::class, mappedBy: 'characterA', cascade: ['remove'])]
    private Collection $relationsAsA;

    /** @var Collection<int, CharacterRelation> */
    #[ORM\OneToMany(targetEntity: CharacterRelation::class, mappedBy: 'characterB', cascade: ['remove'])]
    private Collection $relationsAsB;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 255)]
    public string $name = '' {
        get => $this->name;
        set => $this->name = trim($value);
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $firstName = '' {
        get => $this->firstName;
        set => $this->firstName = trim($value);
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $lastName = '' {
        get => $this->lastName;
        set => $this->lastName = trim($value);
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $nickname = '' {
        get => $this->nickname;
        set => $this->nickname = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $description = '' {
        get => $this->description;
        set => $this->description = trim($value);
    }

    /**
     * Rôle narratif : 'protagonist' | 'antagonist' | 'secondary' | 'figurant'
     */
    #[ORM\Column(type: 'string', length: 50)]
    public string $role = '' {
        get => $this->role;
        set {
            if ($value !== '' && !in_array($value, self::VALID_ROLES)) {
                throw new \InvalidArgumentException("Rôle invalide : $value");
            }
            $this->role = $value;
        }
    }

    #[ORM\Column(type: 'text')]
    public string $biography = '' {
        get => $this->biography;
        set => $this->biography = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $goals = '' {
        get => $this->goals;
        set => $this->goals = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $motivations = '' {
        get => $this->motivations;
        set => $this->motivations = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $characterArc = '' {
        get => $this->characterArc;
        set => $this->characterArc = trim($value);
    }

    #[ORM\Column(type: 'json')]
    public array $aliases = [];

    // ─── Nullable (getter/setter classiques) ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $portraitFilename = null;

    // ─── Timestamps ───────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    // ─── Constructeur ─────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->relationsAsA = new ArrayCollection();
        $this->relationsAsB = new ArrayCollection();
        $this->createdAt    = new \DateTimeImmutable();
        $this->updatedAt    = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Toutes les relations du personnage (en tant que A ou B).
     * @return Collection<int, CharacterRelation>
     */
    public function getAllRelations(): Collection
    {
        return new ArrayCollection(array_merge(
            $this->relationsAsA->toArray(),
            $this->relationsAsB->toArray()
        ));
    }

    // ─── Getters read-only ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // ─── Nullable ────────────────────────────────────────────────────────────

    public function getPortraitFilename(): ?string { return $this->portraitFilename; }
    public function setPortraitFilename(?string $v): static { $this->portraitFilename = $v; return $this; }

    // ─── Relation ────────────────────────────────────────────────────────────

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    /** @return Collection<int, CharacterRelation> */
    public function getRelationsAsA(): Collection { return $this->relationsAsA; }

    /** @return Collection<int, CharacterRelation> */
    public function getRelationsAsB(): Collection { return $this->relationsAsB; }

    // ─── Setters compat Symfony Forms ────────────────────────────────────────

    public function setName(string $v): static { $this->name = trim($v); return $this; }
    public function setFirstName(string $v): static { $this->firstName = trim($v); return $this; }
    public function setLastName(string $v): static { $this->lastName = trim($v); return $this; }
    public function setNickname(string $v): static { $this->nickname = trim($v); return $this; }
    public function setDescription(string $v): static { $this->description = trim($v); return $this; }
    public function setRole(string $v): static { $this->role = $v; return $this; }
    public function setBiography(string $v): static { $this->biography = trim($v); return $this; }
    public function setGoals(string $v): static { $this->goals = trim($v); return $this; }
    public function setMotivations(string $v): static { $this->motivations = trim($v); return $this; }
    public function setCharacterArc(string $v): static { $this->characterArc = trim($v); return $this; }
    public function setAliases(array $v): static { $this->aliases = $v; return $this; }

    // ─── Getters compat Twig/code existant ───────────────────────────────────

    public function getName(): string { return $this->name; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getNickname(): string { return $this->nickname; }
    public function getDescription(): string { return $this->description; }
    public function getRole(): string { return $this->role; }
    public function getBiography(): string { return $this->biography; }
    public function getGoals(): string { return $this->goals; }
    public function getMotivations(): string { return $this->motivations; }
    public function getCharacterArc(): string { return $this->characterArc; }
    public function getAliases(): array { return $this->aliases; }
}
