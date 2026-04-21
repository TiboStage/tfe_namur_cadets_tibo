<?php

namespace App\Entity;

use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Membre collaborateur d'un projet.
 * Clé primaire composite (project_id, user_id).
 *
 * Rôles : 'contributor' | 'editor' | 'lead'
 */
#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
#[ORM\Table(name: 'project_member')]
class ProjectMember
{
    private const VALID_ROLES = ['contributor', 'editor', 'lead'];

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectMembers')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn( false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $role = 'contributor' {
        get => $this->role;
        set {
            if (!in_array($value, self::VALID_ROLES)) {
                throw new \InvalidArgumentException("Rôle invalide : $value");
            }
            $this->role = $value;
        }
    }

    // ─── Timestamp ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    // Compat Forms
    public function setRole(string $v): static { $this->role = $v; return $this; }
    public function getRole(): string { return $this->role; }
}
