<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
abstract class AbstractProjectResourceController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly TranslatorInterface    $translator,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // MÉTHODES ABSTRAITES (à définir dans les enfants)
    // ═══════════════════════════════════════════════════════════════

    abstract protected function getEntityClass(): string;
    abstract protected function getFormClass(): string;
    abstract protected function getRoutePrefix(): string;
    abstract protected function getTemplatePrefix(): string;
    abstract protected function getEntityVariable(): string;
    abstract protected function getEntitiesVariable(): string;
    abstract protected function getTranslationKey(): string;

    // ═══════════════════════════════════════════════════════════════
    // MÉTHODES CRUD GÉNÉRIQUES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Liste des entités du projet
     */
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        return $this->render($this->getTemplatePrefix() . '/index.html.twig', [
            'project' => $project,
            $this->getEntitiesVariable() => $this->getEntities($project),
        ]);
    }

    /**
     * Créer une nouvelle entité
     */
    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        $entity = $this->createEntity($project);

        $form = $this->createForm($this->getFormClass(), $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            $this->flashSuccess($this->getTranslationKey() . '.flash.created');

            return $this->redirectToRoute($this->getRoutePrefix() . '_show', [
                'project_slug' => $project->getSlug(),
                'id' => $entity->getId(),
            ]);
        }

        return $this->render($this->getTemplatePrefix() . '/new.html.twig', [
            'project' => $project,
            'form' => $form,
            $this->getEntityVariable() => $entity,
        ]);
    }

    /**
     * Afficher une entité
     *
     * CORRECTION : Récupération manuelle de l'entité au lieu de MapEntity
     * car MapEntity ne fonctionne pas bien avec les classes abstraites.
     */
    public function show(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        int $id
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        // Récupération manuelle de l'entité
        $entity = $this->em->getRepository($this->getEntityClass())->find($id);

        if (!$entity || $entity->getProject() !== $project) {
            throw $this->createNotFoundException(
                sprintf('%s not found', $this->getEntityVariable())
            );
        }

        return $this->render($this->getTemplatePrefix() . '/show.html.twig', [
            'project' => $project,
            $this->getEntityVariable() => $entity,
        ]);
    }

    /**
     * Modifier une entité
     *
     * CORRECTION : Récupération manuelle de l'entité au lieu de MapEntity
     */
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        int $id
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        // Récupération manuelle de l'entité
        $entity = $this->em->getRepository($this->getEntityClass())->find($id);

        if (!$entity || $entity->getProject() !== $project) {
            throw $this->createNotFoundException(
                sprintf('%s not found', $this->getEntityVariable())
            );
        }

        $form = $this->createForm($this->getFormClass(), $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->flashSuccess($this->getTranslationKey() . '.flash.updated');

            return $this->redirectToRoute($this->getRoutePrefix() . '_show', [
                'project_slug' => $project->getSlug(),
                'id' => $entity->getId(),
            ]);
        }

        return $this->render($this->getTemplatePrefix() . '/edit.html.twig', [
            'project' => $project,
            'form' => $form,
            $this->getEntityVariable() => $entity,
        ]);
    }

    /**
     * Supprimer une entité
     *
     * CORRECTION : Récupération manuelle de l'entité au lieu de MapEntity
     */
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        int $id
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

        // Récupération manuelle de l'entité
        $entity = $this->em->getRepository($this->getEntityClass())->find($id);

        if (!$entity || $entity->getProject() !== $project) {
            throw $this->createNotFoundException(
                sprintf('%s not found', $this->getEntityVariable())
            );
        }

        $tokenName = 'delete_' . $this->getTranslationKey() . '_' . $entity->getId();

        if ($this->isCsrfTokenValid($tokenName, $request->getPayload()->get('_token'))) {
            $this->em->remove($entity);
            $this->em->flush();

            $this->flashSuccess($this->getTranslationKey() . '.flash.deleted');
        }

        return $this->redirectToRoute($this->getRoutePrefix() . '_index', [
            'project_slug' => $project->getSlug(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTHODES UTILITAIRES (peuvent être surchargées)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Créer une nouvelle instance de l'entité
     */
    protected function createEntity(Project $project): object
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();
        $entity->setProject($project);

        return $entity;
    }

    /**
     * Récupérer les entités du projet
     */
    protected function getEntities(Project $project): iterable
    {
        $method = 'get' . ucfirst($this->getEntitiesVariable());
        return $project->$method();
    }

    /**
     * Flash message de succès
     */
    protected function flashSuccess(string $key, array $params = []): void
    {
        $this->addFlash(
            'success',
            $this->translator->trans($key, $params, 'validators')
        );
    }
}
