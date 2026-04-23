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
    )
    {
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTHODES ABSTRAITES (à définir dans les enfants)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Classe de l'entité (ex: Character::class)
     */
    abstract protected function getEntityClass(): string;

    /**
     * Classe du formulaire (ex: CharacterFormType::class)
     */
    abstract protected function getFormClass(): string;

    /**
     * Préfixe des routes (ex: 'app_character')
     */
    abstract protected function getRoutePrefix(): string;

    /**
     * Préfixe des templates (ex: 'workshop/projects/characters')
     */
    abstract protected function getTemplatePrefix(): string;

    /**
     * Nom de la variable dans le template (ex: 'character')
     */
    abstract protected function getEntityVariable(): string;

    /**
     * Nom de la variable au pluriel (ex: 'characters')
     */
    abstract protected function getEntitiesVariable(): string;

    /**
     * Clé de traduction pour les flash messages (ex: 'character')
     */
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
        Request                                                   $request,
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
     */
    public function show(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] object                             $entity
    ): Response
    {
        $this->checkProjectAccess($project, 'view');

        return $this->render($this->getTemplatePrefix() . '/show.html.twig', [
            'project' => $project,
            $this->getEntityVariable() => $entity,
        ]);
    }

    /**
     * Modifier une entité
     */
    public function edit(
        Request                                                   $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] object                             $entity
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

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
     */
    public function delete(
        Request                                                   $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] object                             $entity
    ): Response
    {
        $this->checkProjectAccess($project, 'edit');

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
     * Peut être surchargée pour customiser l'initialisation
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
     * Peut être surchargée pour customiser la requête
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
