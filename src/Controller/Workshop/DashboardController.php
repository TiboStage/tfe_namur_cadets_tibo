<?php

namespace App\Controller\Workshop;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    /**
     * Note : la route s'appelle app_workshop_dashboard pour correspondre
     * à la redirection après login dans LoginFormAuthenticator.
     */
    public function index(ProjectRepository $projectRepository): Response
    {
        $user = $this->getUser();

        // Récupère les projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(
            ['createdBy' => $user],
            ['updatedAt' => 'DESC'],
            limit: 4
        );

        return $this->render('workshop/index.html.twig', [
            'projects' => $projects,
        ]);
    }
}
