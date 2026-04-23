<?php

namespace App\Controller\Workshop;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {}

    public function index(): Response
    {
        $user = $this->getUser();

        // ✅ OPTIMISÉ : 1 seule requête SQL avec JOIN + GROUP BY
        $projectsWithStats = $this->projectRepository->findByUserWithStats($user);

        // Extraire les projets et calculer les totaux
        $projects = [];
        $totalCharacters = 0;
        $totalLocations = 0;
        $totalScenes = 0;

        foreach ($projectsWithStats as $row) {
            $projects[] = $row[0];  // L'entité Project
            $totalCharacters += (int) $row['character_count'];
            $totalLocations += (int) $row['location_count'];
            $totalScenes += (int) $row['scene_count'];
        }

        // Projets récents (5 derniers)
        $recentProjects = array_slice($projects, 0, 5);

        return $this->render('workshop/dashboard.html.twig', [
            'projects'         => $projects,
            'recentProjects'   => $recentProjects,
            'projects_count'   => count($projects),
            'total_characters' => $totalCharacters,
            'total_locations'  => $totalLocations,
            'total_scenes'     => $totalScenes,
        ]);
    }
}
