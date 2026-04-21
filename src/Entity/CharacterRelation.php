<?php

namespace App\Entity;

use App\Repository\CharacterRelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Relation entre deux personnages.
 *
 * Types : 'ally' | 'enemy' | 'family' | 'love' | 'rival' | 'mentor' | 'protege' | 'unknown'
 * isBidirectional = true  → A↔B (symétrique)
 * isBidirectional = false → A→B uniquement (asymétrique)
 */
#[ORM\Entity(repositoryClass: CharacterRelationRepository::class)]
#[ORM\Table(name: 'character_relation')]
class CharacterRelation
{
    private const VALID_TYPES = ['ally', 'enemy', 'family', 'love', 'rival', 'mentor', 'protege', 'unknown'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'relationsAsA')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $characterA = null;

    #[ORM\ManyToOne(targetEntity: Character::class, inversedBy: 'relationsAsB')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $characterB = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $relationType = '' {
        get => $this->relationType;
        set {
            if ($value !== '' && !in_array($value, self::VALID_TYPES)) {
                throw new \InvalidArgumentException("Type de relation invalide : $value");
            }
            $this->relationType = $value;
        }
    }

    #[ORM\Column(type: 'text')]
    public string $description = '' {
        get => $this->description;
        set => $this->description = trim($value);
    }

    #[ORM\Column(type: 'boolean')]
    public bool $isBidirectional = true;

    // ─── Timestamp ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // ─── Constructeur ─────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ─── Getters read-only ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function getCharacterA(): ?Character { return $this->characterA; }
    public function setCharacterA(?Character $v): static { $this->characterA = $v; return $this; }

    public function getCharacterB(): ?Character { return $this->characterB; }
    public function setCharacterB(?Character $v): static { $this->characterB = $v; return $this; }

    // ─── Setters compat Symfony Forms ────────────────────────────────────────

    public function setRelationType(string $v): static { $this->relationType = $v; return $this; }
    public function setDescription(string $v): static { $this->description = trim($v); return $this; }
    public function setIsBidirectional(bool $v): static { $this->isBidirectional = $v; return $this; }

    // ─── Getters compat Twig/code existant ───────────────────────────────────

    public function getRelationType(): string { return $this->relationType; }
    public function getDescription(): string { return $this->description; }
    public function isBidirectional(): bool { return $this->isBidirectional; }
}
