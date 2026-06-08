<?php

namespace App\Controller\Workshop;

use App\Repository\ActivityLogRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository     $projectRepository,
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    public function index(): Response
    {
        $user = $this->getUser();

        // ── Projets avec stats (1 requête SQL) ───────────────────────────────
        $projectsWithStats = $this->projectRepository->findByUserWithStats($user);

        $projects        = [];
        $totalCharacters = 0;
        $totalLocations  = 0;
        $totalScenes     = 0;

        foreach ($projectsWithStats as $row) {
            $projects[]       = $row[0];
            $totalCharacters += (int) $row['character_count'];
            $totalLocations  += (int) $row['location_count'];
            $totalScenes     += (int) $row['scene_count'];
        }

        // ── Activité récente réelle (ActivityLog) ─────────────────────────────
        $recentActivity = $this->activityLogRepository->findRecentByUser(
            $user->getId(),
            limit: 4
        );

        // ── Projets "en cours" (max 5 pour la section hero) ──────────────────
        $heroProjects = array_slice($projects, 0, 5);

        // ── Projets pour la grille basse (max 4) ─────────────────────────────
        $gridProjects = array_slice($projects, 0, 4);

        return $this->render('workshop/dashboard.html.twig', [
            'projects'         => $projects,
            'hero_projects'    => $heroProjects,
            'grid_projects'    => $gridProjects,
            'recent_activity'  => $recentActivity,
            'projects_count'   => count($projects),
            'total_characters' => $totalCharacters,
            'total_locations'  => $totalLocations,
            'total_scenes'     => $totalScenes,
        ]);
    }
}
