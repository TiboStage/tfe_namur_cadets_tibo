<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Article de documentation — conteneur multilingue.
 * Le titre et le contenu sont dans DocumentationTranslation (fr/nl/en).
 */
#[ORM\Entity(repositoryClass: DocumentationRepository::class)]
#[ORM\Table(name: 'documentation')]
#[ORM\HasLifecycleCallbacks]
class Documentation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Le slug ne peut contenir que des lettres minuscules, chiffres et tirets.')]
    public string $slug = '';

    /** Groupe de navigation : "Démarrage", "Personnages", "Projets", etc. */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    public string $category = '';

    /** Position au sein de la catégorie (tri ascendant). */
    #[ORM\Column]
    public int $orderIndex = 0;

    #[ORM\Column]
    public bool $isPublished = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, DocumentationTranslation> */
    #[ORM\OneToMany(
        targetEntity: DocumentationTranslation::class,
        mappedBy: 'documentation',
        cascade: ['persist', 'remove'],
        fetch: 'EAGER',
    )]
    private Collection $translations;

    /**
     * Locale d'affichage injectée par le contrôleur au moment du rendu.
     * Non persistée — uniquement pour les templates Twig.
     */
    private string $displayLocale = 'fr';

    // ─── Constructeur ────────────────────────────────────────────────────────

    public function __construct()
    {
        $this->createdAt    = new \DateTimeImmutable();
        $this->updatedAt    = new \DateTimeImmutable();
        $this->translations = new ArrayCollection();
    }

    // ─── Lifecycle Callbacks ─────────────────────────────────────────────────

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── Getters de base ─────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ─── Traductions ─────────────────────────────────────────────────────────

    /** @return Collection<int, DocumentationTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    /** Retourne la traduction exacte pour une locale, ou null si absente. */
    public function getTranslation(string $locale): ?DocumentationTranslation
    {
        foreach ($this->translations as $t) {
            if ($t->locale === $locale) {
                return $t;
            }
        }

        return null;
    }

    /**
     * Retourne la meilleure traduction disponible :
     * locale demandée → fallback 'fr' → null.
     */
    public function getLocalizedTranslation(string $locale): ?DocumentationTranslation
    {
        return $this->getTranslation($locale) ?? $this->getTranslation('fr');
    }

    /** Locales pour lesquelles une traduction existe. */
    public function getAvailableLocales(): array
    {
        return array_values(
            $this->translations->map(fn (DocumentationTranslation $t) => $t->locale)->toArray()
        );
    }

    // ─── Display locale (injectée par le contrôleur) ──────────────────────────

    public function setDisplayLocale(string $locale): void
    {
        $this->displayLocale = $locale;
    }

    public function getDisplayLocale(): string
    {
        return $this->displayLocale;
    }

    // ─── Accesseurs Twig (transparents vis-à-vis des templates existants) ─────

    /**
     * Retourne le titre dans la locale d'affichage, avec fallback FR.
     * Twig appelle cette méthode via {{ article.title }}.
     */
    public function getTitle(): string
    {
        return $this->getLocalizedTranslation($this->displayLocale)?->title ?? '';
    }

    /**
     * Retourne le contenu Markdown dans la locale d'affichage, avec fallback FR.
     * Twig appelle cette méthode via {{ article.content }}.
     */
    public function getContent(): string
    {
        return $this->getLocalizedTranslation($this->displayLocale)?->content ?? '';
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public static function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }
}
