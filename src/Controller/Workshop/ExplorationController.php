<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Repository\CommentRepository;
use App\Repository\GenreRepository;
use App\Repository\ProjectMemberRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page d'exploration — discovery + recherche filtrée.
 *
 * index()  → page statique avec sections découverte
 * search() → endpoint AJAX qui retourne des cartes pré-rendues (HTML côté serveur)
 *            pour réutiliser exactement les mêmes composants Twig que les sections discovery
 */
final class ExplorationController extends AbstractController
{
    private const PAGE_SIZE = 5;

    public function __construct(
        private readonly ProjectRepository       $projectRepository,
        private readonly UserRepository          $userRepository,
        private readonly ProjectMemberRepository $projectMemberRepository,
        private readonly GenreRepository         $genreRepository,
        private readonly CommentRepository       $commentRepository,
    ) {}

    // ── Page principale ───────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $locale     = $request->getLocale();
        $allGenres  = $this->genreRepository->findAllForAdmin();
        $genresJson = array_values(array_filter(array_map(
            fn($g) => $g->isActive ? [
                'slug'  => $g->slug,
                'label' => $g->getLabel($locale),
                'types' => $g->projectTypes,
            ] : null,
            $allGenres
        )));

        // 6 derniers par type pour les sections discovery
        $latestFilms  = $this->projectRepository->findPublicForExploration('film',      null, 5, 0);
        $latestSeries = $this->projectRepository->findPublicForExploration('serie',     null, 5, 0);
        $latestGames  = $this->projectRepository->findPublicForExploration('jeu_video', null, 5, 0);
        $featuredCreators = $this->userRepository->findTopCreatorsWithCount(4);
        $viewerRolesMap   = $this->buildViewerRolesMap();

        // Comptage commentaires en une seule requête pour toutes les sections
        $allDiscoveryIds = array_map(
            fn(Project $p) => $p->getId(),
            array_merge($latestFilms, $latestSeries, $latestGames)
        );
        $commentCountsMap = $this->commentRepository->countByProjectIds($allDiscoveryIds);

        return $this->render('workshop/explore/index.html.twig', [
            'latest_films'       => $latestFilms,
            'latest_series'      => $latestSeries,
            'latest_games'       => $latestGames,
            'featured_creators'  => $featuredCreators,
            'viewer_roles_map'   => $viewerRolesMap,
            'comment_counts_map' => $commentCountsMap,
            'initial_query'     => $request->query->get('q',     ''),
            'initial_type'      => $request->query->get('type',  ''),
            'initial_genre'     => $request->query->get('genre', ''),
            'initial_sort'      => $request->query->get('sort',  'recent'),
            'genres_json'       => $genresJson,
        ]);
    }

    // ── Endpoint AJAX ─────────────────────────────────────────────────────────

    public function search(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $q      = trim($request->query->get('q',     ''));
        $mode   = $request->query->get('mode',  'projet');
        $type   = $request->query->get('type')  ?: null;
        $genre  = $request->query->get('genre') ?: null;
        $sort   = $request->query->get('sort',  'recent');
        $page   = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * self::PAGE_SIZE;
        $locale = $request->getLocale();

        // ── Mode auteur ───────────────────────────────────────────────────────
        if ($mode === 'auteur') {
            $authorSort = in_array($sort, ['az', 'za'], true) ? $sort : 'az';
            $rows       = $this->userRepository->searchCreatorsWithProjectCount($q, self::PAGE_SIZE, $offset, $authorSort);
            $total      = $this->userRepository->countCreatorsForSearch($q);

            // Rendu des cartes auteur via le même partial créateur
            $cardsHtml = '';
            foreach ($rows as $row) {
                $user  = $row[0];
                $count = (int) $row['project_count'];
                $cardsHtml .= $this->renderView('workshop/_partials/_creator_card_search.html.twig', [
                    'user'          => $user,
                    'project_count' => $count,
                ]);
            }

            return $this->json([
                'total'     => $total,
                'page'      => $page,
                'pages'     => $total > 0 ? max(1, (int) ceil($total / self::PAGE_SIZE)) : 0,
                'cardsHtml' => $cardsHtml,
                'mode'      => 'auteur',
            ]);
        }

        // ── Mode projet ───────────────────────────────────────────────────────
        $validSort = in_array($sort, ['recent', 'oldest', 'az', 'za'], true) ? $sort : 'recent';

        $projects = $this->projectRepository->findPublicForExploration(
            $type, $q !== '' ? $q : null, self::PAGE_SIZE, $offset, $genre, $validSort
        );
        $total = $this->projectRepository->countPublicForExploration(
            $type, $q !== '' ? $q : null, $genre
        );

        // Map rôle visiteur pour les cartes (bouton "Atelier" si owner/collab)
        $viewerRolesMap = $this->buildViewerRolesMap();

        // Comptage commentaires en une seule requête pour les résultats de recherche
        $projectIds = array_map(fn(Project $p) => $p->getId(), $projects);
        $commentCountsMap = $this->commentRepository->countByProjectIds($projectIds);

        // Rendu serveur des cartes → même composant Twig que les sections discovery
        $cardsHtml = '';
        foreach ($projects as $project) {
            $cardsHtml .= $this->renderView('workshop/_partials/_project_card_public.html.twig', [
                'project'       => $project,
                'comment_count' => $commentCountsMap[$project->getId()] ?? 0,
                'viewer_role'   => $viewerRolesMap[$project->getId()] ?? null,
            ]);
        }

        return $this->json([
            'total'     => $total,
            'page'      => $page,
            'pages'     => $total > 0 ? max(1, (int) ceil($total / self::PAGE_SIZE)) : 0,
            'cardsHtml' => $cardsHtml,
            'mode'      => 'projet',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildViewerRolesMap(): array
    {
        $user = $this->getUser();
        if ($user === null) return [];
        $map = [];
        foreach ($this->projectRepository->findOwnedProjectIds($user->getId()) as $id) {
            $map[$id] = 'owner';
        }
        foreach ($this->projectMemberRepository->findRoleMapByUser($user->getId()) as $id => $role) {
            $map[$id] ??= $role;
        }
        return $map;
    }
}
