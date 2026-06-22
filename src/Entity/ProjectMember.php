<?php

namespace App\Entity;

use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Membre collaborateur d'un projet.
 * Clé primaire composite (project_id, user_id).
 *
 * Rôles :
 *   'reader'      — lecture seule + ajout de notes
 *   'contributor' — écriture du contenu (manuscrit, personnages, lieux, tâches)
 *   'moderator'   — tout + gestion du projet (visibilité, paramètres)
 */
#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
#[ORM\Table(name: 'project_member')]
class ProjectMember
{
    public const ROLE_READER      = 'reader';
    public const ROLE_CONTRIBUTOR = 'contributor';
    public const ROLE_MODERATOR   = 'moderator';

    private const VALID_ROLES = [self::ROLE_READER, self::ROLE_CONTRIBUTOR, self::ROLE_MODERATOR];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE  = 'active';

    private const VALID_STATUSES = [self::STATUS_PENDING, self::STATUS_ACTIVE];

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectMembers')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $role = self::ROLE_READER {
        get => $this->role;
        set {
            if (!in_array($value, self::VALID_ROLES)) {
                throw new \InvalidArgumentException("Rôle invalide : $value");
            }
            $this->role = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $status = self::STATUS_ACTIVE {
        get => $this->status;
        set {
            if (!in_array($value, self::VALID_STATUSES)) {
                throw new \InvalidArgumentException("Statut invalide : $value");
            }
            $this->status = $value;
        }
    }

    // ─── Timestamp ────────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isActive(): bool  { return $this->status === self::STATUS_ACTIVE; }

    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    // Compat Forms
    public function setRole(string $v): static { $this->role = $v; return $this; }
    public function getRole(): string { return $this->role; }
}
