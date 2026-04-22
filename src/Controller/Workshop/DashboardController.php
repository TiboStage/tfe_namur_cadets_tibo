<?php
//
//namespace App\Controller\Workshop;
//
//use App\Repository\ProjectRepository;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\Routing\Attribute\Route;
//use Symfony\Component\Security\Http\Attribute\IsGranted;
//
//#[IsGranted('ROLE_USER')]
//class DashboardController extends AbstractController
//{
//    /**
//     * Note : la route s'appelle app_workshop_dashboard pour correspondre
//     * à la redirection après login dans LoginFormAuthenticator.
//     */
//    public function index(ProjectRepository $projectRepository): Response
//    {
//        $user = $this->getUser();
//
//        // Récupère les projets de l'utilisateur connecté
//        $projects = $projectRepository->findBy(
//            ['createdBy' => $user],
//            ['updatedAt' => 'DESC'],
//            limit: 4
//        );
//
//        return $this->render('workshop/index.html.twig', [
//            'projects' => $projects,
//        ]);
//    }
//}


namespace App\Controller\Workshop;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepo,
    )
    {
    }

    public function index(): Response
    {
        $user = $this->getUser();

        // Récupère tous les projets de l'utilisateur
        $projects = $this->projectRepo->findBy(
            ['createdBy' => $user],
            ['updatedAt' => 'DESC']
        );

        // Calcule les statistiques globales
        $totalCharacters = 0;
        $totalLocations = 0;
        $totalScenes = 0;

        foreach ($projects as $project) {
            $totalCharacters += count($project->getCharacters());
            $totalLocations += count($project->getLocations());
            $totalScenes += count($project->getScenarioElements());
        }

        $stats = [
            'projects_count' => count($projects),
            'characters_count' => $totalCharacters,
            'locations_count' => $totalLocations,
            'scenes_count' => $totalScenes,
        ];

        // Projets récents (5 derniers)
        $recentProjects = array_slice($projects, 0, 5);

        return $this->render('workshop/dashboard.html.twig', [
            'projects' => $projects,
            'recentProjects' => $recentProjects,
            'stats' => $stats,
            'projects_count' => count($projects),
        ]);
    }
}
