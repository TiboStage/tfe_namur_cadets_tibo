<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Repository\ScenarioElementRepository;
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
        private readonly EntityManagerInterface    $em,
        private readonly ScenarioElementRepository $scenarioRepo,
        private readonly TranslatorInterface       $translator,
    ) {}

    /**
     * Affiche la hiérarchie narrative du projet.
     */
    public function index(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'view');

        $rootElements = $this->scenarioRepo->findRootElements($project);

        return $this->render('workshop/projects/scenario/index.html.twig', [
            'project'      => $project,
            'rootElements' => $rootElements,
        ]);
    }

    /**
     * Crée un nouvel élément narratif.
     */
    public function new(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        $depth    = (int) $request->query->get('depth', 1);
        $parentId = $request->query->get('parent_id');
        $parent   = $parentId ? $this->scenarioRepo->find($parentId) : null;

        $elementType = $this->guessElementType($project->projectType, $depth);

        if ($request->isMethod('POST')) {
            $title = trim($request->request->get('title', ''));

            if (empty($title)) {
                $this->addFlash('danger', 'Un titre est requis.');
                return $this->redirectToRoute('app_scenario_index', [
                    'project_slug' => $project->getSlug(),
                ]);
            }

            $element = new ScenarioElement();
            $element->setProject($project)
                ->setParent($parent)
                ->setElementType($elementType)
                ->setDepth($depth)
                ->setTitle($title)
                ->setContent([])
                ->setSummary('')
                ->setOrderIndex($this->scenarioRepo->countByParent($parent, $project));

            $this->em->persist($element);
            $this->em->flush();

            $this->addFlash('success', 'Élément créé !');

            // Les scènes (has_content) ouvrent l'éditeur directement
            if ($depth >= 3) {
                return $this->redirectToRoute('app_scenario_show', [
                    'project_slug' => $project->getSlug(),
                    'id'           => $element->getId(),
                ]);
            }

            return $this->redirectToRoute('app_scenario_index', [
                'project_slug' => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/scenario/new.html.twig', [
            'project'     => $project,
            'parent'      => $parent,
            'depth'       => $depth,
            'elementType' => $elementType,
        ]);
    }

    /**
     * Éditeur de scénario pour une scène.
     */
    public function show(
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): Response {
        $this->checkProjectAccess($project, 'view');

        // Extrait le texte brut depuis le JSONB
        $rawContent = '';
        $content = $element->getContent();
        if (!empty($content) && isset($content[0]['type']) && $content[0]['type'] === 'raw') {
            $rawContent = $content[0]['content'] ?? '';
        }

        // Charge tous les éléments racine pour la sidebar gauche
        $rootElements = $this->scenarioRepo->findRootElementsWithChildren($project);

        return $this->render('workshop/projects/scenario/show.html.twig', [
            'project'      => $project,
            'element'      => $element,
            'rawContent'   => $rawContent,
            'rootElements' => $rootElements,
            'siblings'     => $this->scenarioRepo->findSiblings($element),
        ]);
    }

    /**
     * Sauvegarde le contenu JSONB (appel AJAX depuis l'éditeur).
     */
    public function save(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): JsonResponse {
        $this->checkProjectAccess($project, 'edit');

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

    /**
     * Supprime un élément narratif.
     */
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['project_slug' => 'slug'])] Project $project,
        #[MapEntity(id: 'id')] ScenarioElement $element
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        if ($this->isCsrfTokenValid('delete_scenario_' . $element->getId(), $request->getPayload()->get('_token'))) {
            $this->em->remove($element);
            $this->em->flush();
            $this->addFlash('success', 'Élément supprimé.');
        }

        return $this->redirectToRoute('app_scenario_index', [
            'project_slug' => $project->getSlug(),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function guessElementType(string $projectType, int $depth): string
    {
        $map = [
            'film'      => [1 => 'act',     2 => 'sequence', 3 => 'scene'],
            'serie'     => [1 => 'season',  2 => 'episode',  3 => 'act', 4 => 'scene'],
            'jeu_video' => [1 => 'chapter', 2 => 'level',    3 => 'scene'],
            'custom'    => [1 => 'part',    2 => 'chapter',  3 => 'scene'],
        ];
        return $map[$projectType][$depth] ?? 'scene';
    }
}
