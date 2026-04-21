<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journal d'audit de toutes les actions effectuées dans l'application.
 * project_id nullable → actions hors-projet (ban user, modification profil).
 */
#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    /** Code d'action au format 'entité.verbe' (ex : 'character.create') */
    #[ORM\Column(type: 'string', length: 100)]
    public string $action = '' {
        get => $this->action;
        set => $this->action = trim($value);
    }

    // ─── Nullable (getter/setter classiques) ──────────────────────────────────

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // ─── Timestamp ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    // Compat Forms
    public function setAction(string $v): static { $this->action = trim($v); return $this; }
    public function getAction(): string { return $this->action; }
}
