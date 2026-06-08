<?php

declare(strict_types=1);

namespace App\Twig;

use App\Repository\GenreRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extension Twig pour les genres de projet.
 *
 * Fournit :
 *  - Filtre  {{ 'thriller'|genre_label }}         → "Thriller" (locale courante)
 *  - Filtre  {{ 'thriller'|genre_label('en') }}   → "Thriller" (locale explicite)
 *  - Fonction {{ genre_list('film') }}             → [Genre, Genre, …]
 *  - Fonction {{ genre_uncategorized_label() }}    → "Non classé" / "Uncategorized" / "Niet ingedeeld"
 */
class GenreExtension extends AbstractExtension
{
    /** Libellés "non classé" par locale, si aucun genre n'est défini */
    private const UNCATEGORIZED = [
        'fr' => 'Non classé',
        'nl' => 'Niet ingedeeld',
        'en' => 'Uncategorized',
    ];

    /**
     * Cache en mémoire pour éviter les requêtes répétées durant le même rendu.
     * @var array<string, \App\Entity\Genre>
     */
    private array $cache = [];

    public function __construct(
        private readonly GenreRepository $genreRepository,
        private readonly RequestStack    $requestStack,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('genre_label', $this->genreLabel(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('genre_list',               $this->genreList(...)),
            new TwigFunction('genre_uncategorized_label', $this->genreUncategorizedLabel(...)),
        ];
    }

    // ── Filtre genre_label ────────────────────────────────────────────────────

    /**
     * Traduit un slug de genre dans la locale courante (ou explicite).
     *
     * Usage :
     *   {{ 'thriller'|genre_label }}
     *   {{ feature.value|genre_label('en') }}
     */
    public function genreLabel(string $slug, ?string $locale = null): string
    {
        if ($slug === '' || $slug === 'uncategorized') {
            return $this->genreUncategorizedLabel($locale);
        }

        $locale ??= $this->currentLocale();

        // Utilise le cache en mémoire
        if (!isset($this->cache[$slug])) {
            $genre = $this->genreRepository->findBySlugWithTranslations($slug);
            if ($genre === null) {
                return $slug; // slug inconnu → retourne le slug brut
            }
            $this->cache[$slug] = $genre;
        }

        return $this->cache[$slug]->getLabel($locale);
    }

    // ── Fonction genre_list ───────────────────────────────────────────────────

    /**
     * Retourne la liste des genres actifs pour un type de projet.
     *
     * Usage :
     *   {% for genre in genre_list('film') %}
     *     {{ genre.slug }} → {{ genre.getLabel(app.request.locale) }}
     *   {% endfor %}
     *
     * @return \App\Entity\Genre[]
     */
    public function genreList(string $projectType): array
    {
        return $this->genreRepository->findActiveForType($projectType);
    }

    // ── Fonction genre_uncategorized_label ───────────────────────────────────

    /**
     * Retourne le libellé "Non classé" dans la locale courante.
     */
    public function genreUncategorizedLabel(?string $locale = null): string
    {
        $locale ??= $this->currentLocale();
        return self::UNCATEGORIZED[$locale] ?? self::UNCATEGORIZED['fr'];
    }

    // ── Helper privé ─────────────────────────────────────────────────────────

    private function currentLocale(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? 'fr';
    }
}
