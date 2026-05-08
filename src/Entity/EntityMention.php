<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EntityMentionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace les mentions directionnelles source → cible.
 *
 * Différence avec ProjectMention :
 * - ProjectMention : compteur global d'occurrences par projet
 * - EntityMention  : lien précis source (ScenarioElement/Note) → cible (Character/Location)
 *
 * Exemples :
 * - ScenarioElement #5 cite Character #3
 * - Note #2 cite Location #7
 * - WorldEvent #1 cite Character #4
 */
#[ORM\Entity(repositoryClass: EntityMentionRepository::class)]
#[ORM\Table(name: 'entity_mention')]
#[ORM\Index(columns: ['source_type', 'source_id'], name: 'idx_mention_source')]
#[ORM\Index(columns: ['target_type', 'target_id'], name: 'idx_mention_target')]
class EntityMention
{
    private const VALID_SOURCE_TYPES = ['scenario_element', 'note', 'world_event'];
    private const VALID_TARGET_TYPES = ['character', 'location'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    // ─── Scalaires — property hooks PHP 8.4 ────────────────────────────────

    /** Type de la source : 'scenario_element' | 'note' | 'world_event' */
    #[ORM\Column(type: 'string', length: 50)]
    public string $sourceType = '' {
        get => $this->sourceType;
        set {
            if (!in_array($value, self::VALID_SOURCE_TYPES, strict: true)) {
                throw new \InvalidArgumentException(
                    sprintf("Type source invalide : '%s'. Attendu : %s", $value, implode(', ', self::VALID_SOURCE_TYPES))
                );
            }
            $this->sourceType = $value;
        }
    }

    /** ID de l'entité source (polymorphique, pas de FK stricte) */
    #[ORM\Column(type: 'integer')]
    public int $sourceId = 0;

    /** Type de la cible : 'character' | 'location' */
    #[ORM\Column(type: 'string', length: 50)]
    public string $targetType = '' {
        get => $this->targetType;
        set {
            if (!in_array($value, self::VALID_TARGET_TYPES, strict: true)) {
                throw new \InvalidArgumentException(
                    sprintf("Type cible invalide : '%s'. Attendu : %s", $value, implode(', ', self::VALID_TARGET_TYPES))
                );
            }
            $this->targetType = $value;
        }
    }

    /** ID de l'entité cible (polymorphique, pas de FK stricte) */
    #[ORM\Column(type: 'integer')]
    public int $targetId = 0;

    // ─── Getters ────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    // Setters compat
    public function setSourceType(string $v): static { $this->sourceType = $v; return $this; }
    public function setSourceId(int $v): static { $this->sourceId = $v; return $this; }
    public function setTargetType(string $v): static { $this->targetType = $v; return $this; }
    public function setTargetId(int $v): static { $this->targetId = $v; return $this; }

    public function getSourceType(): string { return $this->sourceType; }
    public function getSourceId(): int { return $this->sourceId; }
    public function getTargetType(): string { return $this->targetType; }
    public function getTargetId(): int { return $this->targetId; }
}
