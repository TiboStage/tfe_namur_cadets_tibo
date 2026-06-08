<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommentVoteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Vote (👍 / 👎) posé par un utilisateur sur un commentaire.
 * Un seul vote par (user, comment) — contrainte UNIQUE en base.
 */
#[ORM\Entity(repositoryClass: CommentVoteRepository::class)]
#[ORM\Table(name: 'comment_vote')]
#[ORM\UniqueConstraint(name: 'uniq_comment_vote', columns: ['comment_id', 'user_id'])]
class CommentVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(name: 'comment_id', nullable: false, onDelete: 'CASCADE')]
    private ?Comment $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** 'up' | 'down' */
    #[ORM\Column(length: 4)]
    public string $value = 'up';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getComment(): ?Comment { return $this->comment; }
    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
