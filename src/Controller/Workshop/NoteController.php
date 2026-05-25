<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Note;
use App\Entity\Project;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ) {}

    /**
     * Liste des notes du projet
     */
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        $notes = $this->noteRepository->findBy(
            ['project' => $project],
            ['createdAt' => 'DESC']
        );

        return $this->render('workshop/projects/notes/index.html.twig', [
            'project' => $project,
            'notes' => $notes,
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

        if ($status === 'all') {
            $notes = $this->noteRepository->findBy(
                ['project' => $project],
                ['createdAt' => 'DESC']
            );
        } else {
            $notes = $this->noteRepository->findBy(
                ['project' => $project, 'status' => $status],
                ['createdAt' => 'DESC']
            );
        }

        return $this->render('workshop/projects/notes/index.html.twig', [
            'project' => $project,
            'notes' => $notes,
            'currentStatus' => $status,
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

        $form = $this->createForm(NoteType::class, $note, [
            'project' => $project,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($note);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'note.flash.created',
                ['%title%' => $note->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_note_index', [
                'project_slug' => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/notes/new.html.twig', [
            'project' => $project,
            'form' => $form,
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
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

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

        return $this->render('workshop/projects/notes/edit.html.twig', [
            'project' => $project,
            'note' => $note,
            'form' => $form,
        ]);
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
