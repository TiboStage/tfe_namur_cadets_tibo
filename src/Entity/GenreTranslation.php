<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GenreTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Traduction d'un genre pour une locale donnée.
 * Chaque genre doit avoir exactement une traduction par locale supportée.
 */
#[ORM\Entity(repositoryClass: GenreTranslationRepository::class)]
#[ORM\Table(name: 'genre_translation')]
#[ORM\UniqueConstraint(name: 'uniq_genre_locale', columns: ['genre_id', 'locale'])]
class GenreTranslation
{
    /** Locales supportées par la plateforme */
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
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Genre::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Genre $genre = null;

    /** Code locale : 'fr', 'nl' ou 'en' */
    #[ORM\Column(type: 'string', length: 5)]
    public string $locale = 'fr';

    /** Libellé traduit du genre (ex : "Thriller", "Thriller", "Thriller") */
    #[ORM\Column(type: 'string', length: 100)]
    public string $label = '';

    // ─── Getters / Setters ──────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getGenre(): ?Genre { return $this->genre; }

    public function setGenre(?Genre $genre): static
    {
        $this->genre = $genre;
        return $this;
    }
}
