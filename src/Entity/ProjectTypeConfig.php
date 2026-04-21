<?php

namespace App\Entity;

use App\Repository\ProjectTypeConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Définit la hiérarchie narrative selon le type de projet.
 * Ex film : depth=1→Acte, depth=2→Séquence, depth=3→Scène
 */
#[ORM\Entity(repositoryClass: ProjectTypeConfigRepository::class)]
#[ORM\Table(name: 'project_type_config')]
class ProjectTypeConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $projectType = '' {
        get => $this->projectType;
        set => $this->projectType = trim($value);
    }

    #[ORM\Column(type: 'integer')]
    public int $depth = 1 {
        get => $this->depth;
        set {
            if ($value < 1) {
                throw new \InvalidArgumentException("La profondeur doit être >= 1");
            }
            $this->depth = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 50)]
    public string $elementType = '' {
        get => $this->elementType;
        set => $this->elementType = trim($value);
    }

    #[ORM\Column(type: 'string', length: 100)]
    public string $labelSingular = '' {
        get => $this->labelSingular;
        set => $this->labelSingular = trim($value);
    }

    #[ORM\Column(type: 'string', length: 100)]
    public string $labelPlural = '' {
        get => $this->labelPlural;
        set => $this->labelPlural = trim($value);
    }

    #[ORM\Column(type: 'boolean')]
    public bool $hasContent = false;

    #[ORM\Column(type: 'boolean')]
    public bool $hasDuration = false;

    #[ORM\Column(type: 'integer')]
    public int $defaultDurationSeconds = 0;

    #[ORM\Column(type: 'string', length: 50)]
    public string $icon = '' {
        get => $this->icon;
        set => $this->icon = trim($value);
    }

    #[ORM\Column(type: 'string', length: 7)]
    public string $color = '#6B7280' {
        get => $this->color;
        set {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                throw new \InvalidArgumentException("Couleur invalide : $value");
            }
            $this->color = strtoupper($value);
        }
    }

    // ─── Getter id ────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    // ─── Compat Forms ─────────────────────────────────────────────────────────

    public function setProjectType(string $v): static { $this->projectType = trim($v); return $this; }
    public function setDepth(int $v): static { $this->depth = $v; return $this; }
    public function setElementType(string $v): static { $this->elementType = trim($v); return $this; }
    public function setLabelSingular(string $v): static { $this->labelSingular = trim($v); return $this; }
    public function setLabelPlural(string $v): static { $this->labelPlural = trim($v); return $this; }
    public function setHasContent(bool $v): static { $this->hasContent = $v; return $this; }
    public function setHasDuration(bool $v): static { $this->hasDuration = $v; return $this; }
    public function setDefaultDurationSeconds(int $v): static { $this->defaultDurationSeconds = $v; return $this; }
    public function setIcon(string $v): static { $this->icon = trim($v); return $this; }
    public function setColor(string $v): static { $this->color = $v; return $this; }

    public function getProjectType(): string { return $this->projectType; }
    public function getDepth(): int { return $this->depth; }
    public function getElementType(): string { return $this->elementType; }
    public function getLabelSingular(): string { return $this->labelSingular; }
    public function getLabelPlural(): string { return $this->labelPlural; }
    public function isHasContent(): bool { return $this->hasContent; }
    public function isHasDuration(): bool { return $this->hasDuration; }
    public function getDefaultDurationSeconds(): int { return $this->defaultDurationSeconds; }
    public function getIcon(): string { return $this->icon; }
    public function getColor(): string { return $this->color; }
}
