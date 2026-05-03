<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Form\ProjectFormType;
use App\Repository\ProjectRepository;
use App\Service\DurationCalculator;
use App\Service\FeatureManager;
use App\Service\ProjectConfigGenerator;
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
        private readonly ProjectConfigGenerator $configGenerator,
        private readonly FeatureManager $featureManager,
        private readonly DurationCalculator $durationCalculator,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // LISTE & DASHBOARD
    // ═══════════════════════════════════════════════════════════════

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

    // ═══════════════════════════════════════════════════════════════
    // FUNNEL CRÉATION - ÉTAPE 1 : CHOIX DU MODE
    // ═══════════════════════════════════════════════════════════════

    public function newStep1(): Response
    {
        return $this->render('workshop/projects/new/step1_mode.html.twig');
    }

    // ═══════════════════════════════════════════════════════════════
    // FUNNEL CRÉATION - ÉTAPE 2 : TYPE + CONFIGURATION
    // ═══════════════════════════════════════════════════════════════

    public function newStep2(Request $request): Response
    {
        $mode = $request->query->get('mode'); // 'simplifie' ou 'custom'

        if (!in_array($mode, ['simplifie', 'custom'])) {
            return $this->redirectToRoute('app_project_new_step1');
        }

        if ($mode === 'simplifie') {  // ← Vérifie cette ligne !
            return $this->render('workshop/projects/new/step2_simplifie.html.twig', [
                'mode' => $mode,
            ]);
        } else {
            return $this->render('workshop/projects/new/step2_custom.html.twig', [
                'mode' => $mode,
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // FUNNEL CRÉATION - ÉTAPE 3 : FINALISATION + CRÉATION
    // ═══════════════════════════════════════════════════════════════

    public function newStep3(Request $request): Response
    {
        $session = $request->getSession();

        // Si c'est un POST depuis step2 (sans titre)
        if ($request->isMethod('POST') && !$request->request->has('title')) {
            // Stocker les données en session
            $session->set('funnel_data', [
                'mode' => $request->request->get('mode'),
                'project_type' => $request->request->get('project_type'),
                'genres' => $request->request->all('genres') ?? [],
                'custom_structure' => $request->request->all('custom_structure') ?? [],
                'selected_features' => $request->request->all('selected_features') ?? [],
            ]);

            // REDIRECTION vers step3 en GET
            return $this->redirectToRoute('app_project_new_step3');
        }

        // Récupération depuis la session
        $funnelData = $session->get('funnel_data', []);

        if (empty($funnelData)) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_project_new_step1');
        }

        $mode = $funnelData['mode'];
        $projectType = $funnelData['project_type'];
        $genres = $funnelData['genres'];
        $customStructure = $funnelData['custom_structure'] ?? [];
        $selectedFeatures = $funnelData['selected_features'] ?? [];

        // Validation
        if (!$mode || !in_array($projectType, ['film', 'serie', 'jeu_video'])) {
            $this->addFlash('error', $this->translator->trans('project_new.error.invalid_data', [], 'project_new'));
            return $this->redirectToRoute('app_project_new_step1');
        }

        // Si soumission finale avec titre
        if ($request->request->has('title')) {
            $result = $this->handleProjectCreation(
                $request,
                $mode,
                $projectType,
                $genres,
                $customStructure,
                $selectedFeatures
            );

            // Nettoyer la session
            $session->remove('funnel_data');

            return $result;
        }

        // Affichage du formulaire step3
        return $this->render('workshop/projects/new/step3_final.html.twig', [
            'mode' => $mode,
            'project_type' => $projectType,
            'genres' => $genres,
            'custom_structure' => $customStructure,
            'selected_features' => $selectedFeatures,
        ]);
    }



    // ═══════════════════════════════════════════════════════════════
    // CRÉATION EFFECTIVE DU PROJET
    // ═══════════════════════════════════════════════════════════════

    private function handleProjectCreation(
        Request $request,
        string $mode,
        string $projectType,
        array $genres,
        array $customStructure,
        array $selectedFeatures
    ): Response {
        $user = $this->getUser();

        // 1. Création du projet
        $project = new Project();
        $project->setCreatedBy($user);
        $project->title = $request->request->get('title');
        $project->description = $request->request->get('description', '');
        $project->projectType = $projectType;
        $project->isPublic = false; // Toujours privé par défaut

        // 2. Settings JSONB
        $settings = [
            'genres'      => $genres,
            'custom_mode' => ($mode === 'custom'),
        ];

        // Storylines pour Série (si feature activée)
        if ($projectType === 'serie' || ($mode === 'custom' && in_array('storyline_tracker', $selectedFeatures))) {
            $settings['storylines'] = [];
        }

        $project->settings = $settings;

        // 3. Custom Structure (si mode custom)
        if ($mode === 'custom' && !empty($customStructure)) {
            $project->customStructure = $customStructure;
        }

        // 4. Gestion de l'image (upload)
        $coverFile = $request->files->get('cover_image');
        if ($coverFile) {
            // TODO: Gestion upload avec VichUploader ou service custom
            // $filename = $this->uploadService->upload($coverFile, 'projects');
            // $project->setCoverFilename($filename);
        }

        $this->em->persist($project);
        $this->em->flush(); // FLUSH 1 : Nécessaire pour avoir l'ID du projet

        // 5. Génération des ProjectTypeConfig
        if ($mode === 'simplifie') {
            $this->configGenerator->generateDefaultConfigs($project, $projectType);
        } else {
            // Custom : configs depuis customStructure
            $this->configGenerator->generateCustomConfigs($project, $customStructure);
        }

        // 6. Activation des Features (mode Custom uniquement)
        if ($mode === 'custom' && !empty($selectedFeatures)) {
            $this->featureManager->activateFeatures($project, $selectedFeatures);
        }

        $this->em->flush(); // FLUSH 2 : Sauvegarde configs + features

        // 7. Flash + Redirection
        $this->addFlash('success', $this->translator->trans('project_new.flash.created', [], 'validators'));

        return $this->redirectToRoute('app_project_show', [
            'slug' => $project->getSlug(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ÉDITION & SUPPRESSION
    // ═══════════════════════════════════════════════════════════════

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
                'slug' => $project->getSlug(),
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
