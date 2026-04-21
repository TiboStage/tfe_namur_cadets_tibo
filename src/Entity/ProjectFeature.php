<?php

namespace App\Entity;

use App\Repository\ProjectFeatureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Valeur d'une feature personnalisée pour un projet précis.
 * Ex : project_id=5, feature_key="genre", value="fantasy"
 */
#[ORM\Entity(repositoryClass: ProjectFeatureRepository::class)]
#[ORM\Table(name: 'project_feature')]
class ProjectFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectFeatures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    // ─── Scalaires — property hooks PHP 8.4 ──────────────────────────────────

    #[ORM\Column(type: 'string', length: 50)]
    public string $featureKey = '' {
        get => $this->featureKey;
        set => $this->featureKey = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $value = '' {
        get => $this->value;
        set => $this->value = trim($value);
    }

    // ─── Getters ─────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    // Compat Forms
    public function setFeatureKey(string $v): static { $this->featureKey = trim($v); return $this; }
    public function setValue(string $v): static { $this->value = trim($v); return $this; }
    public function getFeatureKey(): string { return $this->featureKey; }
    public function getValue(): string { return $this->value; }
}
