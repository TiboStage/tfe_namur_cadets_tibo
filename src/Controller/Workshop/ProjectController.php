<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Form\ProjectFormType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProjectRepository $projectRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    public function index(): Response
    {
        $user = $this->getUser();

        $projects = $this->projectRepository->findBy(
            ['createdBy' => $user],
            ['updatedAt' => 'DESC']
        );

        return $this->render('workshop/projects/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    public function new(Request $request): Response
    {
        $user = $this->getUser();
        $project = new Project();
        $project->setCreatedBy($user);

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($project);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('project.flash.created', [], 'validators'));

            return $this->redirectToRoute('app_project_show', [
                'slug' => $project->slug,
            ]);
        }

        return $this->render('workshop/projects/new.html.twig', [
            'form'    => $form,
            'project' => $project,
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    /**
     * Dashboard du projet avec stats et accès rapides
     */
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'view');

        // Stats du projet
        $stats = [
            'scenes_count'     => count($project->getScenarioElements()),
            'characters_count' => count($project->getCharacters()),
            'locations_count'  => count($project->getLocations()),
            'last_update'      => $project->getUpdatedAt(),
        ];

        // Dernières modifications (top 5 éléments narratifs)
        $recentUpdates = $this->em->getRepository(ScenarioElement::class)
            ->findBy(
                ['project' => $project],
                ['updatedAt' => 'DESC'],
                5
            );

        return $this->render('workshop/projects/show.html.twig', [
            'project'       => $project,
            'stats'         => $stats,
            'recentUpdates' => $recentUpdates,
        ]);
    }

    public function edit(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('project.flash.updated', [], 'validators'));

            return $this->redirectToRoute('app_project_show', [
                'slug' => $project->slug,
            ]);
        }

        return $this->render('workshop/projects/edit.html.twig', [
            'form'    => $form,
            'project' => $project,
        ]);
    }

    public function delete(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        if ($this->isCsrfTokenValid('delete_project_' . $project->getId(), $request->getPayload()->get('_token'))) {
            $this->em->remove($project);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('project.flash.deleted', [], 'validators'));
        }

        return $this->redirectToRoute('app_project_index');
    }
}
