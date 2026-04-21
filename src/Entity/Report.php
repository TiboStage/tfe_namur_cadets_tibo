<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Signalement d'un projet public par un utilisateur.
 * Statuts : 'pending' | 'reviewed' | 'dismissed'
 */
#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'report')]
class Report
{
    private const VALID_STATUSES = ['pending', 'reviewed', 'dismissed'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $reporter = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'text')]
    public string $reason = '' {
        get => $this->reason;
        set => $this->reason = trim($value);
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $status = 'pending' {
        get => $this->status;
        set {
            if (!in_array($value, self::VALID_STATUSES)) {
                throw new \InvalidArgumentException("Statut invalide : $value");
            }
            $this->status = $value;
        }
    }

    // ─── Nullable ─────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminFeedback = null;

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

    public function getReporter(): ?User { return $this->reporter; }
    public function setReporter(?User $reporter): static { $this->reporter = $reporter; return $this; }

    public function getAdminFeedback(): ?string { return $this->adminFeedback; }
    public function setAdminFeedback(?string $v): static { $this->adminFeedback = $v; return $this; }

    // Compat Forms
    public function setReason(string $v): static { $this->reason = trim($v); return $this; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getReason(): string { return $this->reason; }
    public function getStatus(): string { return $this->status; }
}
