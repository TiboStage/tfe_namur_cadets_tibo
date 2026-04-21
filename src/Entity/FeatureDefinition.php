<?php

namespace App\Entity;

use App\Repository\FeatureDefinitionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Définit les champs personnalisés disponibles pour un type de projet.
 * Les valeurs réelles saisies par l'utilisateur sont dans ProjectFeature.
 */
#[ORM\Entity(repositoryClass: FeatureDefinitionRepository::class)]
#[ORM\Table(name: 'feature_definition')]
class FeatureDefinition
{
    private const VALID_VALUE_TYPES = ['text', 'select', 'boolean', 'number', 'date'];

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

    #[ORM\Column(type: 'string', length: 50)]
    public string $featureKey = '' {
        get => $this->featureKey;
        set => $this->featureKey = trim($value);
    }

    #[ORM\Column(type: 'string', length: 100)]
    public string $label = '' {
        get => $this->label;
        set => $this->label = trim($value);
    }

    #[ORM\Column(type: 'text')]
    public string $description = '' {
        get => $this->description;
        set => $this->description = trim($value);
    }

    #[ORM\Column(type: 'string', length: 20)]
    public string $valueType = 'text' {
        get => $this->valueType;
        set {
            if (!in_array($value, self::VALID_VALUE_TYPES)) {
                throw new \InvalidArgumentException("Type de valeur invalide : $value");
            }
            $this->valueType = $value;
        }
    }

    #[ORM\Column(type: 'string', length: 255)]
    public string $defaultValue = '' {
        get => $this->defaultValue;
        set => $this->defaultValue = trim($value);
    }

    #[ORM\Column(type: 'json')]
    public array $options = [];

    #[ORM\Column(type: 'boolean')]
    public bool $isRequired = false;

    #[ORM\Column(type: 'integer')]
    public int $displayOrder = 0;

    // ─── Getter id ────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    // ─── Compat Forms ─────────────────────────────────────────────────────────

    public function setProjectType(string $v): static { $this->projectType = trim($v); return $this; }
    public function setFeatureKey(string $v): static { $this->featureKey = trim($v); return $this; }
    public function setLabel(string $v): static { $this->label = trim($v); return $this; }
    public function setDescription(string $v): static { $this->description = trim($v); return $this; }
    public function setValueType(string $v): static { $this->valueType = $v; return $this; }
    public function setDefaultValue(string $v): static { $this->defaultValue = trim($v); return $this; }
    public function setOptions(array $v): static { $this->options = $v; return $this; }
    public function setIsRequired(bool $v): static { $this->isRequired = $v; return $this; }
    public function setDisplayOrder(int $v): static { $this->displayOrder = $v; return $this; }

    public function getProjectType(): string { return $this->projectType; }
    public function getFeatureKey(): string { return $this->featureKey; }
    public function getLabel(): string { return $this->label; }
    public function getDescription(): string { return $this->description; }
    public function getValueType(): string { return $this->valueType; }
    public function getDefaultValue(): string { return $this->defaultValue; }
    public function getOptions(): array { return $this->options; }
    public function isRequired(): bool { return $this->isRequired; }
    public function getDisplayOrder(): int { return $this->displayOrder; }
}
