<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\WorldEvent;
use App\Form\WorldEventType;
use App\Repository\WorldEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour gérer la chronologie narrative d'un projet.
 *
 * WorldEvent permet de créer une timeline d'événements avec dates narratives
 * (année, mois, jour) et de les lier à des lieux.
 */
#[Route('/workshop/projects/{project_slug}/timeline')]
#[IsGranted('ROLE_USER')]
class WorldEventController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly WorldEventRepository $worldEventRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Liste chronologique des événements du projet
     */
    #[Route('', name: 'app_world_event_index', methods: ['GET'])]
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        $events = $this->worldEventRepository->findByProjectChronological($project);

        return $this->render('workshop/projects/timeline/index.html.twig', [
            'project' => $project,
            'events' => $events,
        ]);
    }

    /**
     * Créer un nouvel événement
     */
    #[Route('/new', name: 'app_world_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        $event = new WorldEvent();
        $event->setProject($project);

        $form = $this->createForm(WorldEventType::class, $event, [
            'project' => $project,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'world_event.flash.created',
                ['%title%' => $event->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_world_event_index', [
                'project_slug' => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/timeline/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    /**
     * Afficher un événement
     */
    #[Route('/{id}', name: 'app_world_event_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        WorldEvent $event
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        if ($event->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        return $this->render('workshop/projects/timeline/show.html.twig', [
            'project' => $project,
            'event' => $event,
        ]);
    }

    /**
     * Éditer un événement
     */
    #[Route('/{id}/edit', name: 'app_world_event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        WorldEvent $event
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        if ($event->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(WorldEventType::class, $event, [
            'project' => $project,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'world_event.flash.updated',
                ['%title%' => $event->getTitle()],
                'workshop_interface'
            ));

            return $this->redirectToRoute('app_world_event_show', [
                'project_slug' => $project->getSlug(),
                'id' => $event->getId(),
            ]);
        }

        return $this->render('workshop/projects/timeline/edit.html.twig', [
            'project' => $project,
            'event' => $event,
            'form' => $form,
        ]);
    }

    /**
     * Supprimer un événement
     */
    #[Route('/{id}/delete', name: 'app_world_event_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        WorldEvent $event
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        if ($event->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $title = $event->getTitle();
            $this->em->remove($event);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans(
                'world_event.flash.deleted',
                ['%title%' => $title],
                'workshop_interface'
            ));
        }

        return $this->redirectToRoute('app_world_event_index', [
            'project_slug' => $project->getSlug(),
        ]);
    }
}
