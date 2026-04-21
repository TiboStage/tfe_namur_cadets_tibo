<?php

namespace App\Entity;

use App\Repository\PublicCommentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Commentaire public posté sur un projet public.
 */
#[ORM\Entity(repositoryClass: PublicCommentRepository::class)]
#[ORM\Table(name: 'public_comment')]
#[ORM\HasLifecycleCallbacks]
class PublicComment
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
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: OwnerResponse::class, mappedBy: 'comment', cascade: ['remove'])]
    private ?OwnerResponse $ownerResponse = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'text')]
    public string $content = '' {
        get => $this->content;
        set => $this->content = trim($value);
    }

    #[ORM\Column(type: 'boolean')]
    public bool $isApproved = true;

    #[ORM\Column(type: 'boolean')]
    public bool $isSpam = false;

    // ─── Timestamps ───────────────────────────────────────────────────────────

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

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getOwnerResponse(): ?OwnerResponse { return $this->ownerResponse; }

    // Compat Forms
    public function setContent(string $v): static { $this->content = trim($v); return $this; }
    public function setIsApproved(bool $v): static { $this->isApproved = $v; return $this; }
    public function setIsSpam(bool $v): static { $this->isSpam = $v; return $this; }
    public function getContent(): string { return $this->content; }
    public function isApproved(): bool { return $this->isApproved; }
    public function isSpam(): bool { return $this->isSpam; }
}
