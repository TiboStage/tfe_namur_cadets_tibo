<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Alerte in-app destinée à un utilisateur.
 */
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'text')]
    public string $content = '' {
        get => $this->content;
        set => $this->content = trim($value);
    }

    #[ORM\Column(type: 'boolean')]
    public bool $isRead = false;

    // ─── Nullable ─────────────────────────────────────────────────────────────

    /** URL vers la ressource concernée (ex : '/projects/5/characters/7') */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $link = null;

    // ─── Timestamp ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $v): static { $this->link = $v; return $this; }

    public function markAsRead(): static { $this->isRead = true; return $this; }

    // Compat Forms
    public function setContent(string $v): static { $this->content = trim($v); return $this; }
    public function setIsRead(bool $v): static { $this->isRead = $v; return $this; }
    public function getContent(): string { return $this->content; }
    public function isRead(): bool { return $this->isRead; }
}
