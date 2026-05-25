<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour gérer les tâches d'un projet.
 *
 * Les tâches permettent de suivre l'avancement du travail en équipe
 * avec statuts (todo, in_progress, review, done) et priorités.
 */
#[Route('/workshop/projects/{project_slug}/tasks')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Liste des tâches en vue Kanban
     */
    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        $allTasks = $this->taskRepository->findBy(
            ['project' => $project],
            ['priority' => 'DESC', 'createdAt' => 'DESC']
        );

        // Groupe les tâches par statut pour la vue Kanban
        $tasksByStatus = [
            'todo' => [],
            'in_progress' => [],
            'review' => [],
            'done' => [],
        ];

        foreach ($allTasks as $task) {
            $status = $task->getStatus();
            if (isset($tasksByStatus[$status])) {
                $tasksByStatus[$status][] = $task;
            }
        }

        return $this->render('workshop/projects/tasks/index.html.twig', [
            'project' => $project,
            'tasksByStatus' => $tasksByStatus,
            'allTasks' => $allTasks,
        ]);
    }

    /**
     * Créer une nouvelle tâche
     */
    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        $task = new Task();
        $task->setProject($project);
        $task->setCreatedBy($this->getUser());

        $form = $this->createForm(TaskType::class, $task, [
            'project' => $project,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'task.flash.created',
                ['%title%' => $task->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_task_index', [
                'project_slug' => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/tasks/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    /**
     * Afficher une tâche
     */
    #[Route('/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Task $task
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        if ($task->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        return $this->render('workshop/projects/tasks/show.html.twig', [
            'project' => $project,
            'task' => $task,
        ]);
    }

    /**
     * Éditer une tâche
     */
    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Task $task
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        if ($task->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(TaskType::class, $task, [
            'project' => $project,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'task.flash.updated',
                ['%title%' => $task->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_task_show', [
                'project_slug' => $project->getSlug(),
                'id' => $task->getId(),
            ]);
        }

        return $this->render('workshop/projects/tasks/edit.html.twig', [
            'project' => $project,
            'task' => $task,
            'form' => $form,
        ]);
    }

    /**
     * Supprimer une tâche
     */
    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Task $task
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        if ($task->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $title = $task->getTitle();
            $this->em->remove($task);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'task.flash.deleted',
                ['%title%' => $title],
                'workshop_interface'
            ));
        }

        return $this->redirectToRoute('app_task_index', [
            'project_slug' => $project->getSlug(),
        ]);
    }

    /**
     * Changer rapidement le statut d'une tâche (AJAX)
     */
    #[Route('/{id}/status', name: 'app_task_change_status', methods: ['POST'])]
    public function changeStatus(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Task $task
    ): JsonResponse
    {
        $this->checkProjectAccess($project, 'edit');

        if ($task->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $newStatus = $request->request->get('status');

        if (in_array($newStatus, ['todo', 'in_progress', 'review', 'done'])) {
            $task->setStatus($newStatus);
            $this->em->flush();

            return $this->json(['success' => true, 'status' => $newStatus]);
        }

        return $this->json(['success' => false], 400);
    }
}
