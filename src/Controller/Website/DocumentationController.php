<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Repository\DocumentationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page publique de documentation avec sidebar Markdown.
 */
final class DocumentationController extends AbstractController
{
    public function __construct(
        private readonly DocumentationRepository $docRepository,
    ) {}

    /**
     * Page d'accueil de la documentation.
     * Redirige vers le premier article publié, ou affiche une page d'intro.
     */
    public function index(Request $request): Response
    {
        $nav   = $this->docRepository->findPublishedGroupedByCategory();
        $first = null;

        foreach ($nav as $articles) {
            if (!empty($articles)) {
                $first = $articles[0];
                break;
            }
        }

        // S'il existe au moins un article, on redirige dessus
        if ($first !== null) {
            return $this->redirectToRoute('app_documentation_show', [
                'slug'    => $first->slug,
                '_locale' => $request->getLocale(),
            ]);
        }

        // Sinon : page d'intro vide
        return $this->render('website/pages/documentation/index.html.twig', [
            'nav'          => $nav,
            'current_slug' => null,
            'article'      => null,
        ]);
    }

    /**
     * Affiche un article de documentation.
     */
    public function show(string $slug): Response
    {
        $article = $this->docRepository->findOneBy([
            'slug'        => $slug,
            'isPublished' => true,
        ]);

        if ($article === null) {
            throw $this->createNotFoundException("Article de documentation introuvable : $slug");
        }

        $nav = $this->docRepository->findPublishedGroupedByCategory();

        return $this->render('website/pages/documentation/show.html.twig', [
            'nav'          => $nav,
            'article'      => $article,
            'current_slug' => $slug,
        ]);
    }
}
