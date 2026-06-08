<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Signalement d'un projet, d'un commentaire ou d'un utilisateur.
 * Traité par les modérateurs et administrateurs.
 */
#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'report')]
class Report
{
    public const TYPE_PROJECT = 'project';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_USER    = 'user';

    public const REASON_SPAM          = 'spam';
    public const REASON_INAPPROPRIATE = 'inappropriate';
    public const REASON_HARASSMENT    = 'harassment';
    public const REASON_COPYRIGHT     = 'copyright';
    public const REASON_OTHER         = 'other';

    public const REASONS = [
        self::REASON_SPAM          => 'Spam / Publicité non sollicitée',
        self::REASON_INAPPROPRIATE => 'Contenu inapproprié ou choquant',
        self::REASON_HARASSMENT    => 'Harcèlement / Discours haineux',
        self::REASON_COPYRIGHT     => 'Violation du droit d\'auteur',
        self::REASON_OTHER         => 'Autre raison',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_REVIEWED  = 'reviewed';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_ACTIONED  = 'actioned';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** project | comment | user */
    #[ORM\Column(length: 20)]
    public string $targetType = '';

    #[ORM\Column(length: 50)]
    public string $reason = '';

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    /** pending | reviewed | dismissed | actioned */
    #[ORM\Column(length: 20)]
    public string $status = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reporter_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $reporter = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'target_project_id', nullable: true, onDelete: 'CASCADE')]
    private ?Project $targetProject = null;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(name: 'target_comment_id', nullable: true, onDelete: 'CASCADE')]
    private ?Comment $targetComment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'target_user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $targetUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reviewed_by_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getReporter(): ?User { return $this->reporter; }
    public function setReporter(?User $u): static { $this->reporter = $u; return $this; }

    public function getTargetProject(): ?Project { return $this->targetProject; }
    public function setTargetProject(?Project $p): static { $this->targetProject = $p; return $this; }

    public function getTargetComment(): ?Comment { return $this->targetComment; }
    public function setTargetComment(?Comment $c): static { $this->targetComment = $c; return $this; }

    public function getTargetUser(): ?User { return $this->targetUser; }
    public function setTargetUser(?User $u): static { $this->targetUser = $u; return $this; }

    public function getReviewedBy(): ?User { return $this->reviewedBy; }
    public function setReviewedBy(?User $u): static { $this->reviewedBy = $u; return $this; }

    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }
    public function setReviewedAt(?\DateTimeImmutable $at): static { $this->reviewedAt = $at; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTarget(): Project|Comment|User|null
    {
        return match($this->targetType) {
            self::TYPE_PROJECT => $this->targetProject,
            self::TYPE_COMMENT => $this->targetComment,
            self::TYPE_USER    => $this->targetUser,
            default            => null,
        };
    }

    public function getReasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
}
