<?php

namespace App\Entity;

use App\Repository\ProjectMentionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Comptabilise les occurrences de mentions (@personnage / #lieu) dans un projet.
 * Mis à jour à chaque sauvegarde d'un ScenarioElement.
 */
#[ORM\Entity(repositoryClass: ProjectMentionRepository::class)]
#[ORM\Table(name: 'project_mention')]
class ProjectMention
{
    private const VALID_ENTITY_TYPES = ['character', 'location'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $entityType = '' {
        get => $this->entityType;
        set {
            if (!in_array($value, self::VALID_ENTITY_TYPES)) {
                throw new \InvalidArgumentException("Type d'entité invalide : $value");
            }
            $this->entityType = $value;
        }
    }

    /** ID de l'entité (character.id ou location.id — polymorphique, pas de FK) */
    #[ORM\Column(type: 'integer')]
    public int $entityId = 0;

    #[ORM\Column(type: 'integer')]
    public int $occurrenceCount = 0;

    // ─── Getters ─────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function increment(): static { $this->occurrenceCount++; return $this; }

    // Compat Forms
    public function setEntityType(string $v): static { $this->entityType = $v; return $this; }
    public function setEntityId(int $v): static { $this->entityId = $v; return $this; }
    public function setOccurrenceCount(int $v): static { $this->occurrenceCount = $v; return $this; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): int { return $this->entityId; }
    public function getOccurrenceCount(): int { return $this->occurrenceCount; }
}
