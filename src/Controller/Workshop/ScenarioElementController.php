<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Repository\NoteRepository;
use App\Repository\ProjectTypeConfigRepository;
use App\Repository\ScenarioElementRepository;
use App\Repository\TaskRepository;
use App\Service\ProjectConfigGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class ScenarioElementController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly EntityManagerInterface       $em,
        private readonly ScenarioElementRepository    $scenarioRepo,
        private readonly NoteRepository               $noteRepository,
        private readonly TaskRepository               $taskRepository,
        private readonly ProjectTypeConfigRepository  $configRepo,
        private readonly ProjectConfigGenerator       $configGenerator,
        private readonly TranslatorInterface          $translator,
    ) {}

    // ─── Index ───────────────────────────────────────────────────────────────

    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'view');

        $rootElements = $this->scenarioRepo->findRootElementsWithChildren($project);
        $configMap    = $this->configRepo->findMapByProject($project);

        // Notes actives groupées par elementId
        $notesByElement = [];
        foreach ($project->getNotes() as $note) {
            if ($note->getLinkedEntityType() === 'scenario_element' && $note->getStatus() !== 'archived') {
                $notesByElement[$note->getLinkedEntityId()][] = $note;
            }
        }

        // Comptage de mots total
        $totalWordCount = 0;
        foreach ($project->getScenarioElements() as $el) {
            if ($el->isLeaf()) {
                $text = implode(' ', array_map(fn($b) => $b['content'] ?? '', $el->getContent()));
                $totalWordCount += $text ? count(preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY)) : 0;
            }
        }

        return $this->render('workshop/projects/scenario/index.html.twig', [
            'project'        => $project,
            'rootElements'   => $rootElements,
            'notesByElement' => $notesByElement,
            'totalWordCount' => $totalWordCount,
            'configMap'      => $configMap,
            'maxDepth'       => $configMap ? max(array_keys($configMap)) : 3,
        ]);
    }

    // ─── New ─────────────────────────────────────────────────────────────────

    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $depth     = max(1, (int) $request->query->get('depth', 1));
        $parentId  = $request->query->get('parent_id');
        $parent    = $parentId ? $this->scenarioRepo->find($parentId) : null;
        $configMap = $this->configRepo->findMapByProject($project);

        // Configs absentes (projet ancien ou import) → génération automatique
        if (empty($configMap)) {
            $this->configGenerator->generateConfigsForDepth($project, $project->projectType, 3);
            $this->em->flush();
            $configMap = $this->configRepo->findMapByProject($project);
        }

        $config = $configMap[$depth] ?? null;

        // depth demandé hors plage → on repart sur le depth 1
        if ($config === null) {
            $depth  = 1;
            $config = $configMap[1] ?? null;
        }

        if ($config === null) {
            $this->addFlash('danger', 'Impossible de déterminer la structure de ce projet.');
            return $this->redirectToRoute('app_manuscript_index', [
                '_locale'      => $request->getLocale(),
                'project_slug' => $project->getSlug(),
            ]);
        }

        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));

            if (empty($title)) {
                return $this->render('workshop/projects/scenario/new.html.twig', [
                    'project'   => $project,
                    'parent'    => $parent,
                    'depth'     => $depth,
                    'config'    => $config,
                    'configMap' => $configMap,
                    'error'     => 'Un titre est requis.',
                ]);
            }

            $element = new ScenarioElement();
            $element->setProject($project)
                ->setParent($parent)
                ->setElementType($config->elementType)
                ->setDepth($depth)
                ->setTitle($title)
                ->setContent([])
                ->setSummary('')
                ->setHasContent($config->hasContent)
                ->setOrderIndex($this->scenarioRepo->countByParent($parent, $project));

            $this->em->persist($element);
            $this->em->flush();

            return $this->redirectToRoute('app_manuscript_show', [
                '_locale'      => $request->getLocale(),
                'project_slug' => $project->getSlug(),
                'id'           => $element->getId(),
            ]);
        }

        return $this->render('workshop/projects/scenario/new.html.twig', [
            'project'   => $project,
            'parent'    => $parent,
            'depth'     => $depth,
            'config'    => $config,
            'configMap' => $configMap,
            'error'     => null,
        ]);
    }

    // ─── Show ────────────────────────────────────────────────────────────────

    public function show(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): Response {
        $this->checkProjectAccess($project, 'view');

        if ($element->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        // Les dossiers n'ont plus de page propre — on redirige vers l'index manuscrit
        if (!$element->isLeaf()) {
            return $this->redirectToRoute('app_manuscript_index', [
                '_locale'      => $request->getLocale(),
                'project_slug' => $project->getSlug(),
            ]);
        }

        $rootElements  = $this->scenarioRepo->findRootElementsWithChildren($project);
        $configMap     = $this->configRepo->findMapByProject($project);
        $currentConfig = $configMap[$element->getDepth()] ?? null;

        $elementNotes = $this->noteRepository->findByLinkedEntity(
            $project->getId(), 'scenario_element', $element->getId()
        );
        $elementTasks = $this->taskRepository->findByLinkedEntity(
            $project->getId(), 'scenario_element', $element->getId()
        );

        return $this->render('workshop/projects/scenario/show.html.twig', [
            'project'       => $project,
            'element'       => $element,
            'rootElements'  => $rootElements,
            'siblings'      => $this->scenarioRepo->findSiblings($element),
            'elementNotes'  => $elementNotes,
            'elementTasks'  => $elementTasks,
            'configMap'     => $configMap,
            'currentConfig' => $currentConfig,
            'maxDepth'      => $configMap ? max(array_keys($configMap)) : 3,
            'settingsUrl'   => $this->generateUrl('app_manuscript_settings', [
                '_locale'      => 'fr',
                'project_slug' => $project->getSlug(),
                'id'           => $element->getId(),
            ]),
        ]);
    }

    // ─── Save (AJAX — contenu Fountain) ──────────────────────────────────────

    public function save(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

        if ($element->getProject() !== $project) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['content'])) {
            return new JsonResponse(['error' => 'Contenu manquant'], 400);
        }

        $element->setContent($data['content']);

        if (isset($data['summary'])) {
            $element->setSummary(mb_substr($data['summary'], 0, 255));
        }

        $this->em->flush();

        return new JsonResponse([
            'success'    => true,
            'message'    => 'Sauvegardé',
            'updated_at' => $element->getUpdatedAt()->format('H:i:s'),
        ]);
    }

    // ─── Settings (PATCH — durée, résumé, visibilité) ────────────────────────

    public function settings(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

        if ($element->getProject() !== $project) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['summary'])) {
            $element->setSummary(mb_substr(trim((string) $data['summary']), 0, 255));
        }

        if (isset($data['durationSeconds'])) {
            $element->setDurationSeconds(max(0, (int) $data['durationSeconds']));
        }

        if (isset($data['isPublic'])) {
            $element->isPublic = (bool) $data['isPublic'];
        }

        $this->em->flush();

        return new JsonResponse([
            'success'    => true,
            'updated_at' => $element->getUpdatedAt()->format('H:i:s'),
        ]);
    }

    // ─── Rename (PATCH JSON) ──────────────────────────────────────────────────

    public function rename(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

        if ($element->getProject() !== $project) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $data  = json_decode($request->getContent(), true);
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            return new JsonResponse(['error' => 'Title required'], 400);
        }

        $element->setTitle($title);
        $this->em->flush();

        return new JsonResponse(['success' => true, 'title' => $element->getTitle()]);
    }

    // ─── Reorder (PATCH JSON) ────────────────────────────────────────────────

    public function reorder(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

        $data     = json_decode($request->getContent(), true);
        $ids      = (array) ($data['ids'] ?? []);
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== null
            ? (int) $data['parent_id']
            : null;

        foreach ($ids as $index => $id) {
            $element = $this->scenarioRepo->find((int) $id);
            if (!$element || $element->getProject() !== $project) {
                continue;
            }
            if ($element->getParent()?->getId() !== $parentId) {
                continue;
            }
            $element->setOrderIndex($index);
        }

        $this->em->flush();

        return new JsonResponse(['ok' => true]);
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        if ($element->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_scenario_' . $element->getId(), $request->getPayload()->get('_token'))) {
            $this->em->remove($element);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('scenario.element_deleted', [], 'flash_messages'));
        }

        return $this->redirectToRoute('app_manuscript_index', [
            '_locale'      => $request->getLocale(),
            'project_slug' => $project->getSlug(),
        ]);
    }
}
