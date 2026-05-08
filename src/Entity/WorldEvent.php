<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorldEventRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Événement narratif dans la chronologie d'un projet.
 * Permet de construire une timeline et de lier des lieux à des moments clés.
 */
#[ORM\Entity(repositoryClass: WorldEventRepository::class)]
#[ORM\Table(name: 'world_event')]
#[ORM\HasLifecycleCallbacks]
class WorldEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Location $location = null;

    // ─── Scalaires — property hooks PHP 8.4 ────────────────────────────────

    #[ORM\Column(type: 'string', length: 255)]
    public string $title = '' {
        get => $this->title;
        set => $this->title = trim($value);
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null {
        get => $this->description;
        set => $this->description = $value !== null ? trim($value) : null;
    }

    /** Année narrative (peut être négative pour l'avant "an 0") */
    #[ORM\Column(type: 'integer')]
    public int $year = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $month = null {
        get => $this->month;
        set {
            if ($value !== null && ($value < 1 || $value > 12)) {
                throw new \InvalidArgumentException("Mois invalide : $value (1-12 attendu)");
            }
            $this->month = $value;
        }
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $day = null {
        get => $this->day;
        set {
            if ($value !== null && ($value < 1 || $value > 31)) {
                throw new \InvalidArgumentException("Jour invalide : $value (1-31 attendu)");
            }
            $this->day = $value;
        }
    }

    // ─── Timestamps ─────────────────────────────────────────────────────────

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ─── Getters ────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getProject(): ?Project { return $this->project; }
    public function getLocation(): ?Location { return $this->location; }

    // ─── Setters ────────────────────────────────────────────────────────────

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;
        return $this;
    }

    // Setters compat Symfony Forms
    public function setTitle(string $v): static { $this->title = trim($v); return $this; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function setYear(int $v): static { $this->year = $v; return $this; }
    public function setMonth(?int $v): static { $this->month = $v; return $this; }
    public function setDay(?int $v): static { $this->day = $v; return $this; }

    // Getters compat Twig
    public function getTitle(): string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    public function getYear(): int { return $this->year; }
    public function getMonth(): ?int { return $this->month; }
    public function getDay(): ?int { return $this->day; }

    /**
     * Retourne une date narrative formatée.
     * Ex: "An 342, Mois 3, Jour 15" ou juste "An -100"
     */
    public function getFormattedDate(): string
    {
        $parts = ["An {$this->year}"];
        if ($this->month !== null) $parts[] = "Mois {$this->month}";
        if ($this->day !== null) $parts[] = "Jour {$this->day}";
        return implode(', ', $parts);
    }
}
