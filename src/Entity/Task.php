<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tâche d'équipe assignable, pouvant être liée à une entité du projet.
 *
 * Statuts : todo | in_progress | review | done
 * Priorités : low | normal | high | urgent
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
#[ORM\HasLifecycleCallbacks]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedTo = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    /**
     * Statut : 'todo' | 'in_progress' | 'review' | 'done'
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'todo';

    /**
     * Priorité : 'low' | 'normal' | 'high' | 'urgent'
     */
    #[ORM\Column(type: 'string', length: 20)]
    private string $priority = 'normal';

    /**
     * Type de l'entité liée (optionnel) : 'scenario_element' | 'character' | 'location'
     */
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $linkedEntityType = null;

    /**
     * ID de l'entité liée (optionnel).
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $linkedEntityId = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Getters & Setters ────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getAssignedTo(): ?User { return $this->assignedTo; }
    public function setAssignedTo(?User $assignedTo): static { $this->assignedTo = $assignedTo; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): static { $this->priority = $priority; return $this; }

    public function getLinkedEntityType(): ?string { return $this->linkedEntityType; }
    public function setLinkedEntityType(?string $linkedEntityType): static { $this->linkedEntityType = $linkedEntityType; return $this; }

    public function getLinkedEntityId(): ?int { return $this->linkedEntityId; }
    public function setLinkedEntityId(?int $linkedEntityId): static { $this->linkedEntityId = $linkedEntityId; return $this; }

    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }
    public function setDueDate(?\DateTimeImmutable $dueDate): static { $this->dueDate = $dueDate; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
