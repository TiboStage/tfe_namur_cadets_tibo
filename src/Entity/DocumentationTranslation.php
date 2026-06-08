<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentationTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Traduction d'un article de documentation pour une locale donnée.
 * Un article peut avoir des traductions en fr, nl et en.
 */
#[ORM\Entity(repositoryClass: DocumentationTranslationRepository::class)]
#[ORM\Table(name: 'documentation_translation')]
#[ORM\UniqueConstraint(name: 'uniq_doc_locale', columns: ['documentation_id', 'locale'])]
class DocumentationTranslation
{
    public const LOCALES = ['fr', 'nl', 'en'];

    public const LOCALE_LABELS = [
        'fr' => 'Français',
        'nl' => 'Nederlands',
        'en' => 'English',
    ];

    public const LOCALE_FLAGS = [
        'fr' => '🇫🇷',
        'nl' => '🇳🇱',
        'en' => '🇬🇧',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Documentation::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Documentation $documentation = null;

    /** Locale : 'fr', 'nl' ou 'en' */
    #[ORM\Column(length: 5)]
    public string $locale = 'fr';

    #[ORM\Column(length: 255)]
    public string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    public string $content = '';

    // ─── Getters / Setters ──────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentation(): ?Documentation
    {
        return $this->documentation;
    }

    public function setDocumentation(?Documentation $documentation): static
    {
        $this->documentation = $documentation;

        return $this;
    }
}
