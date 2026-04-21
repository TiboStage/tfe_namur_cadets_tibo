<?php

namespace App\Entity;

use App\Repository\OwnerResponseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réponse unique du créateur d'un projet à un commentaire public.
 * UNIQUE sur comment_id → 1 réponse max par commentaire.
 */
#[ORM\Entity(repositoryClass: OwnerResponseRepository::class)]
#[ORM\Table(name: 'owner_response')]
class OwnerResponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: PublicComment::class, inversedBy: 'ownerResponse')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', unique: true)]
    private ?PublicComment $comment = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'text')]
    public string $content = '' {
        get => $this->content;
        set => $this->content = trim($value);
    }

    // ─── Timestamp ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getComment(): ?PublicComment { return $this->comment; }
    public function setComment(?PublicComment $comment): static { $this->comment = $comment; return $this; }

    // Compat Forms
    public function setContent(string $v): static { $this->content = trim($v); return $this; }
    public function getContent(): string { return $this->content; }
}
