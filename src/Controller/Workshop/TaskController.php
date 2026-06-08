<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour gérer les tâches d'un projet.
 *
 * Les tâches permettent de suivre l'avancement du travail en équipe
 * avec statuts (todo, in_progress, review, done) et priorités.
 */
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Liste des tâches en vue Kanban
     */
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        $allTasksIncArchived = $this->taskRepository->findBy(
            ['project' => $project],
            ['priority' => 'DESC', 'createdAt' => 'DESC']
        );

        $allTasks     = array_values(array_filter($allTasksIncArchived, fn($t) => $t->getStatus() !== 'archived'));
        $archivedCount = count($allTasksIncArchived) - count($allTasks);

        $today        = new \DateTimeImmutable('today');
        $overdueCount = 0;
        $urgentCount  = 0;
        $doneCount    = 0;

        foreach ($allTasks as $task) {
            if ($task->getStatus() === 'done') {
                $doneCount++;
            } else {
                if ($task->getDueDate() !== null && $task->getDueDate() < $today) {
                    $overdueCount++;
                }
                if ($task->getPriority() === 'urgent') {
                    $urgentCount++;
                }
            }
        }

        return $this->render('workshop/projects/tasks/index.html.twig', [
            'project'        => $project,
            'allTasks'       => $allTasks,
            'elementPaths'   => $this->resolveElementPaths($allTasks),
            'overdueCount'   => $overdueCount,
            'urgentCount'    => $urgentCount,
            'doneCount'      => $doneCount,
            'totalCount'     => count($allTasks),
            'archivedCount'  => $archivedCount,
            'currentStatus'  => 'all',
        ]);
    }

    /**
     * Vue archivées (chargement serveur)
     */
    public function filter(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        string $status
    ): Response {
        $this->checkProjectAccess($project, 'view');

        $tasks = $this->taskRepository->findBy(
            ['project' => $project, 'status' => $status],
            ['priority' => 'DESC', 'createdAt' => 'DESC']
        );

        $allCount = $this->taskRepository->count(['project' => $project]);

        return $this->render('workshop/projects/tasks/index.html.twig', [
            'project'       => $project,
            'allTasks'      => $tasks,
            'elementPaths'  => $this->resolveElementPaths($tasks),
            'overdueCount'  => 0,
            'urgentCount'   => 0,
            'doneCount'     => 0,
            'totalCount'    => $allCount,
            'archivedCount' => $this->taskRepository->count(['project' => $project, 'status' => 'archived']),
            'currentStatus' => $status,
        ]);
    }

    /**
     * Créer une nouvelle tâche
     */
    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        $task = new Task();
        $task->setProject($project);
        $task->setCreatedBy($this->getUser());

        // Pré-lier à un élément narratif si passé en query param depuis l'inspector
        $elementId = (int) $request->query->get('element_id', 0);
        if ($elementId > 0) {
            $task->setLinkedEntityType('scenario_element');
            $task->setLinkedEntityId($elementId);
        }

        $form = $this->createForm(TaskType::class, $task, [
            'project' => $project,
            'action'  => $request->getRequestUri(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->activityLog->log('task.create', $user, $project, $task->getTitle());
            $this->em->flush();

            if ($request->headers->get('Turbo-Frame') === 'modal-frame') {
                return new Response(
                    '<turbo-stream action="refresh"></turbo-stream>',
                    200,
                    ['Content-Type' => 'text/vnd.turbo-stream.html; charset=utf-8']
                );
            }

            $this->addFlash('success', $this->translator->trans(
                'task.flash.created',
                ['%title%' => $task->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_task_index', [
                'project_slug' => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/tasks/_form.html.twig', [
            'project' => $project,
            'task' => $task,
            'form' => $form,
        ]);
    }

    /**
     * Afficher une tâche
     */
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
            'action'  => $request->getRequestUri(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            if ($request->headers->get('Turbo-Frame') === 'modal-frame') {
                return new Response(
                    '<turbo-stream action="refresh"></turbo-stream>',
                    200,
                    ['Content-Type' => 'text/vnd.turbo-stream.html; charset=utf-8']
                );
            }

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

        return $this->render('workshop/projects/tasks/_form.html.twig', [
            'project' => $project,
            'task' => $task,
            'form' => $form,
        ]);
    }

    /**
     * Supprimer une tâche
     */
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

            // Réponse JSON si appel AJAX (optimistic delete)
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

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

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @param Task[] $tasks
     * @return array<int, ScenarioElement>
     */
    private function resolveElementPaths(array $tasks): array
    {
        $result = [];
        $repo   = $this->em->getRepository(ScenarioElement::class);

        foreach ($tasks as $task) {
            if ($task->getLinkedEntityType() === 'scenario_element' && $task->getLinkedEntityId()) {
                $element = $repo->find($task->getLinkedEntityId());
                if ($element instanceof ScenarioElement) {
                    $result[$task->getId()] = $element;
                }
            }
        }

        return $result;
    }

    /**
     * Changer rapidement la priorité d'une tâche (AJAX)
     */
    public function changePriority(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Task $task
    ): JsonResponse
    {
        $this->checkProjectAccess($project, 'edit');

        if ($task->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $newPriority = $request->request->get('priority');

        if (in_array($newPriority, ['low', 'normal', 'high', 'urgent'])) {
            $task->setPriority($newPriority);
            $this->em->flush();

            return $this->json(['success' => true, 'priority' => $newPriority]);
        }
        return $this->json(['success' => false], 400);
    }

    /**
     * Changer rapidement le statut d'une tâche (AJAX)
     */
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

        if (in_array($newStatus, ['todo', 'in_progress', 'review', 'done', 'archived'])) {
            $task->setStatus($newStatus);
            $this->em->flush();

            return $this->json(['success' => true, 'status' => $newStatus]);
        }
        return $this->json(['success' => false], 400);
    }
}
