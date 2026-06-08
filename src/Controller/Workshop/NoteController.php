<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Note;
use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Form\NoteType;
use App\Repository\NoteRepository;
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
 * Contrôleur pour gérer les notes d'un projet.
 *
 * Les notes peuvent être des simples annotations, des todos, ou des notes
 * liées à des entités spécifiques (personnages, lieux, scènes).
 */
#[IsGranted('ROLE_USER')]
class NoteController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly NoteRepository $noteRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Liste des notes du projet
     */
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        $allNotes = $this->noteRepository->findBy(
            ['project' => $project],
            ['createdAt' => 'DESC']
        );

        $notes = array_values(array_filter($allNotes, fn($n) => $n->getStatus() !== 'archived'));

        return $this->render('workshop/projects/notes/index.html.twig', [
            'project'        => $project,
            'notes'          => $notes,
            'totalNotes'     => count($notes),
            'statusCounts'   => $this->countByStatus($allNotes),
            'currentStatus'  => 'all',
            'elementPaths'   => $this->resolveElementPaths($notes),
        ]);
    }

    /**
     * Filtrer les notes par statut
     */
    public function filter(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        string $status
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        $allNotes = $this->noteRepository->findBy(
            ['project' => $project],
            ['createdAt' => 'DESC']
        );

        if ($status === 'all') {
            $notes = array_values(array_filter($allNotes, fn($n) => $n->getStatus() !== 'archived'));
        } else {
            $notes = $this->noteRepository->findBy(
                ['project' => $project, 'status' => $status],
                ['createdAt' => 'DESC']
            );
        }

        return $this->render('workshop/projects/notes/index.html.twig', [
            'project'       => $project,
            'notes'         => $notes,
            'totalNotes'    => count($allNotes),
            'statusCounts'  => $this->countByStatus($allNotes),
            'currentStatus' => $status,
            'elementPaths'  => $this->resolveElementPaths($notes),
        ]);
    }

    /**
     * Créer une nouvelle note
     */
    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        $note = new Note();
        $note->setProject($project);
        $note->setAuthor($this->getUser());

        // Pré-lier à un élément narratif si passé en query param depuis l'inspector
        $elementId = (int) $request->query->get('element_id', 0);
        if ($elementId > 0) {
            $note->setLinkedEntityType('scenario_element');
            $note->setLinkedEntityId($elementId);
        }

        $form = $this->createForm(NoteType::class, $note, [
            'project' => $project,
            'action'  => $request->getRequestUri(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($note);
            $this->em->flush();

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->activityLog->log('note.create', $user, $project, $note->getTitle());
            $this->em->flush();

            // Requête depuis un modal Turbo Frame → rafraîchit la page en place
            if ($request->headers->get('Turbo-Frame') === 'modal-frame') {
                return new Response(
                    '<turbo-stream action="refresh"></turbo-stream>',
                    200,
                    ['Content-Type' => 'text/vnd.turbo-stream.html; charset=utf-8']
                );
            }

            $this->addFlash('success', $this->translator->trans(
                'note.flash.created',
                ['%title%' => $note->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_note_index', [
                'project_slug' => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/notes/form.html.twig', [
            'project' => $project,
            'form' => $form,
            'note' => $note,
        ]);
    }

    /**
     * Afficher une note
     */
    public function show(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Note $note
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        if ($note->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        return $this->render('workshop/projects/notes/show.html.twig', [
            'project' => $project,
            'note' => $note,
        ]);
    }

    /**
     * Éditer une note
     */
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Note $note
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        if ($note->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(NoteType::class, $note, [
            'project' => $project,
            'action'  => $request->getRequestUri(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            // Requête depuis un modal Turbo Frame → rafraîchit la page en place
            if ($request->headers->get('Turbo-Frame') === 'modal-frame') {
                return new Response(
                    '<turbo-stream action="refresh"></turbo-stream>',
                    200,
                    ['Content-Type' => 'text/vnd.turbo-stream.html; charset=utf-8']
                );
            }

            $this->addFlash('success', $this->translator->trans(
                'note.flash.updated',
                ['%title%' => $note->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_note_show', [
                'project_slug' => $project->getSlug(),
                'id' => $note->getId(),
            ]);
        }

        return $this->render('workshop/projects/notes/form.html.twig', [
            'project' => $project,
            'note' => $note,
            'form' => $form,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function changeStatus(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Note $note
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

        if ($note->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $newStatus = $request->request->get('status');
        $valid     = ['note', 'todo', 'done', 'archived'];

        if (!in_array($newStatus, $valid, true)) {
            return $this->json(['success' => false], 400);
        }

        if (!$this->isCsrfTokenValid('note_status_' . $note->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false], 403);
        }

        $note->setStatus($newStatus);
        $this->em->flush();

        return $this->json(['success' => true, 'status' => $newStatus]);
    }

    public function changePriority(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Note $note
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

        if ($note->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $newPriority = $request->request->get('priority');
        $valid       = ['low', 'normal', 'high', 'urgent'];

        if (!in_array($newPriority, $valid, true)) {
            return $this->json(['success' => false], 400);
        }

        if (!$this->isCsrfTokenValid('note_priority_' . $note->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false], 403);
        }

        $note->setPriority($newPriority);
        $this->em->flush();

        return $this->json(['success' => true, 'priority' => $newPriority]);
    }

    /**
     * @param Note[] $notes
     * @return array<string, int>
     */
    private function countByStatus(array $notes): array
    {
        $counts = ['note' => 0, 'todo' => 0, 'done' => 0, 'archived' => 0];
        foreach ($notes as $note) {
            $s = $note->getStatus();
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
        }
        return $counts;
    }

    /**
     * Pour chaque note liée à un scenario_element, résout l'entité et retourne
     * un tableau [noteId => ScenarioElement] pour afficher le chemin dans la vue.
     *
     * @param Note[] $notes
     * @return array<int, ScenarioElement>
     */
    private function resolveElementPaths(array $notes): array
    {
        $result = [];
        $repo   = $this->em->getRepository(ScenarioElement::class);

        foreach ($notes as $note) {
            if ($note->getLinkedEntityType() === 'scenario_element' && $note->getLinkedEntityId()) {
                $element = $repo->find($note->getLinkedEntityId());
                if ($element instanceof ScenarioElement) {
                    $result[$note->getId()] = $element;
                }
            }
        }

        return $result;
    }

    /**
     * Supprimer une note
     */
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        Note $note
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        if ($note->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete' . $note->getId(), $request->request->get('_token'))) {
            $title = $note->getTitle();
            $this->em->remove($note);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'note.flash.deleted',
                ['%title%' => $title],
                'workshop_interface'
            ));
        }

        return $this->redirectToRoute('app_note_index', [
            'project_slug' => $project->getSlug(),
        ]);
    }
}
