<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GenreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Genre narratif d'un projet (Thriller, Polar, Science-Fiction…).
 *
 * Les genres sont gérés par l'admin via l'interface.
 * Chaque genre possède une traduction en fr, nl et en.
 * Le slug est la clé stable stockée dans ProjectFeature.value.
 *
 * Exemple en BDD :
 *   genre        : id=1, slug="thriller", projectTypes=["film","serie"], isActive=true
 *   ProjectFeature: featureKey="genre", value="thriller"
 */
#[ORM\Entity(repositoryClass: GenreRepository::class)]
#[ORM\Table(name: 'genre')]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Slug unique — clé stable stockée dans ProjectFeature.
     * Ex : "thriller", "polar", "science_fiction"
     */
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    public string $slug = '';

    /**
     * Types de projet compatibles.
     * Tableau vide = applicable à tous les types.
     * Ex : ["film", "serie"] = uniquement pour films et séries.
     */
    #[ORM\Column(type: 'json')]
    public array $projectTypes = [];

    /**
     * Genre visible ou masqué sans être supprimé.
     * Un genre inactif n'apparaît plus dans les sélecteurs mais reste
     * affiché sur les projets qui l'ont déjà.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    public bool $isActive = true;

    /**
     * Ordre d'affichage dans les sélecteurs.
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    public int $orderIndex = 0;

    /** @var Collection<int, GenreTranslation> */
    #[ORM\OneToMany(
        targetEntity: GenreTranslation::class,
        mappedBy: 'genre',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    // ─── Getters ─────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    /** @return Collection<int, GenreTranslation> */
    public function getTranslations(): Collection { return $this->translations; }

    // ─── Helpers traduction ───────────────────────────────────────────────────

    /**
     * Retourne le libellé traduit pour la locale donnée.
     * Si la locale demandée n'existe pas, tente le français en fallback.
     * Si aucune traduction n'existe, retourne le slug.
     */
    public function getLabel(string $locale): string
    {
        foreach ($this->translations as $t) {
            if ($t->locale === $locale) {
                return $t->label;
            }
        }
        // Fallback → français
        foreach ($this->translations as $t) {
            if ($t->locale === 'fr') {
                return $t->label;
            }
        }
        return $this->slug;
    }

    /**
     * Retourne la traduction pour une locale, ou null.
     */
    public function getTranslation(string $locale): ?GenreTranslation
    {
        foreach ($this->translations as $t) {
            if ($t->locale === $locale) {
                return $t;
            }
        }
        return null;
    }

    /**
     * Vérifie si le genre est compatible avec un type de projet.
     * Un tableau vide signifie "compatible avec tous les types".
     */
    public function supportsType(string $projectType): bool
    {
        return empty($this->projectTypes) || in_array($projectType, $this->projectTypes, true);
    }
}
