<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Documentation;
use App\Entity\DocumentationTranslation;
use App\Repository\DocumentationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documentation publique dans l'interface workshop.
 * Gère l'affichage multilingue (fr/nl/en) selon la locale de l'URL.
 */
final class DocumentationController extends AbstractController
{
    public function __construct(
        private readonly DocumentationRepository $docRepository,
    ) {}

    // ─── Index ───────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $locale = $request->getLocale();
        $nav    = $this->buildNav($locale);
        $first  = null;

        foreach ($nav as $articles) {
            if (!empty($articles)) {
                $first = $articles[0];
                break;
            }
        }

        if ($first !== null) {
            return $this->redirectToRoute('app_documentation_show', [
                '_locale' => $locale,
                'slug'    => $first->slug,
            ]);
        }

        return $this->render('workshop/documentation/index.html.twig', [
            'nav'          => $nav,
            'current_slug' => null,
            'article'      => null,
            'locale'       => $locale,
        ]);
    }

    // ─── Show ────────────────────────────────────────────────────────────────

    public function show(string $slug, Request $request): Response
    {
        $locale  = $request->getLocale();

        /** @var Documentation|null $article */
        $article = $this->docRepository->createQueryBuilder('d')
            ->leftJoin('d.translations', 't')
            ->addSelect('t')
            ->where('d.slug = :slug')
            ->andWhere('d.isPublished = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if ($article === null) {
            throw $this->createNotFoundException("Article introuvable : $slug");
        }

        /** @var Documentation $article */
        $article->setDisplayLocale($locale);
        $nav = $this->buildNav($locale);

        return $this->render('workshop/documentation/show.html.twig', [
            'nav'           => $nav,
            'article'       => $article,
            'current_slug'  => $slug,
            'locale'        => $locale,
            'locales'       => DocumentationTranslation::LOCALES,
            'locale_labels' => DocumentationTranslation::LOCALE_LABELS,
            'locale_flags'  => DocumentationTranslation::LOCALE_FLAGS,
        ]);
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Construit la nav groupée et injecte displayLocale sur chaque article.
     *
     * @return array<string, Documentation[]>
     */
    private function buildNav(string $locale): array
    {
        $grouped = $this->docRepository->findPublishedGroupedByCategory();

        foreach ($grouped as $articles) {
            foreach ($articles as $doc) {
                $doc->setDisplayLocale($locale);
            }
        }

        return $grouped;
    }
}
