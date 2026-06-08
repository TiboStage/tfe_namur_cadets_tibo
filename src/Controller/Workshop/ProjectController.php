<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ProjectFeature;
use App\Entity\ScenarioElement;
use App\Repository\ActivityLogRepository;
use App\Repository\CommentRepository;
use App\Repository\GenreRepository;
use App\Repository\ProjectMemberRepository;
use App\Repository\ProjectRepository;
use App\Service\ActivityLogService;
use App\Service\ProjectConfigGenerator;
use App\Service\ProjectPermissionService;
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
        private readonly EntityManagerInterface  $em,
        private readonly ProjectRepository       $projectRepository,
        private readonly ProjectMemberRepository $projectMemberRepository,
        private readonly CommentRepository       $commentRepository,
        private readonly TranslatorInterface     $translator,
        private readonly ProjectConfigGenerator  $configGenerator,
        private readonly GenreRepository         $genreRepository,
        private readonly ActivityLogService       $activityLog,
        private readonly ActivityLogRepository    $activityLogRepository,
        private readonly ProjectPermissionService $permissionService,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // LISTE & DASHBOARD
    // ═══════════════════════════════════════════════════════════════

    public function index(Request $request): Response
    {
        $user  = $this->getUser();
        $scope = $request->query->getString('scope', 'all'); // all | mine | other
        $role  = $request->query->getString('role', '');     // '' | contributor | editor | lead
        $page    = max(1, $request->query->getInt('page', 1));
        $perPage = match($request->query->getInt('per_page', 0)) {
            4  => 4,
            10 => 10,
            default => 6,
        };

        // Compteurs bruts (pour les badges des onglets, toujours non filtrés)
        $ownedCount = $this->projectRepository->count(['createdBy' => $user]);
        $collabCount = $this->projectMemberRepository->countByUser($user->getId());

        // Projets dont je suis propriétaire
        $ownedProjects = ($scope !== 'other')
            ? $this->projectRepository->findBy(['createdBy' => $user], ['updatedAt' => 'DESC'])
            : [];
        $this->projectRepository->preloadCollections($ownedProjects);

        // Projets dont je suis collaborateur (filtrés par rôle si demandé)
        $collaborations = ($scope !== 'mine')
            ? $this->projectMemberRepository->findByUser($user->getId(), $role !== '' ? $role : null)
            : [];
        if (!empty($collaborations)) {
            $this->projectRepository->preloadCollections(array_map(fn($m) => $m->getProject(), $collaborations));
        }

        // Liste unifiée [['project' => Project, 'role' => string]]
        $allItems = [];
        foreach ($ownedProjects as $project) {
            $allItems[] = ['project' => $project, 'role' => 'owner'];
        }
        foreach ($collaborations as $member) {
            $allItems[] = ['project' => $member->getProject(), 'role' => $member->getRole()];
        }

        $totalItems = count($allItems);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page       = min($page, $totalPages);
        $items      = array_slice($allItems, ($page - 1) * $perPage, $perPage);

        return $this->render('workshop/projects/index.html.twig', [
            'items'       => $items,
            'owned_count' => $ownedCount,
            'collab_count' => $collabCount,
            'scope'        => $scope,
            'role_filter'  => $role,
            'page'         => $page,
            'total_pages'  => $totalPages,
            'total_items'  => $totalItems,
        ]);
    }

    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'view');

        // Vraie dernière modification : max(project, éléments narratifs)
        $lastUpdate = $project->getUpdatedAt();
        foreach ($project->getScenarioElements() as $el) {
            if ($el->getUpdatedAt() > $lastUpdate) {
                $lastUpdate = $el->getUpdatedAt();
            }
        }

        // Stats du projet
        $stats = [
            'scenes_count'     => count($project->getScenarioElements()),
            'characters_count' => count($project->getCharacters()),
            'locations_count'  => count($project->getLocations()),
            'last_update'      => $lastUpdate,
        ];

        // Dernières modifications (top 5 éléments narratifs)
        $recentUpdates = $this->em->getRepository(ScenarioElement::class)
            ->findBy(
                ['project' => $project],
                ['updatedAt' => 'DESC'],
                5
            );

        // 5 derniers commentaires (uniquement si le projet est en ligne)
        $recentComments = $project->getVisibility() === Project::VISIBILITY_PUBLIC
            ? $this->commentRepository->findRecentByProject($project->getId(), 5)
            : [];

        // Activité récente du projet (ActivityLog)
        $projectActivity = $this->activityLogRepository->findRecentByProject($project->getId(), 8);

        // Progression des tâches
        $totalTasks = count($project->getTasks());
        $doneTasks  = count(array_filter($project->getTasks()->toArray(), fn($t) => $t->getStatus() === 'done'));

        return $this->render('workshop/projects/show.html.twig', [
            'project'         => $project,
            'readonly'        => $this->isReadOnly($project),
            'stats'           => $stats,
            'recentUpdates'   => $recentUpdates,
            'recentComments'  => $recentComments,
            'projectActivity' => $projectActivity,
            'total_tasks'     => $totalTasks,
            'done_tasks'      => $doneTasks,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FUNNEL CRÉATION — ÉTAPE 1 : CHOIX DU TYPE
    // ═══════════════════════════════════════════════════════════════

    public function newStep1(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $type = $request->request->getString('project_type');

            if (!in_array($type, ['film', 'serie', 'jeu_video'], true)) {
                return $this->redirectToRoute('app_project_new_step1', [
                    '_locale' => $request->getLocale(),
                ]);
            }

            $session = $request->getSession();
            $session->set('funnel_type', $type);
            // On efface titre/desc si on change de type en revenant en arrière
            $session->remove('funnel_title');
            $session->remove('funnel_description');

            return $this->redirectToRoute('app_project_new_step2', [
                '_locale' => $request->getLocale(),
            ]);
        }

        return $this->render('workshop/projects/new/step1_type.html.twig', [
            'step' => 1,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FUNNEL CRÉATION — ÉTAPE 2 : TITRE + DESCRIPTION
    // ═══════════════════════════════════════════════════════════════

    public function newStep2(Request $request): Response
    {
        $session = $request->getSession();
        $type    = $session->get('funnel_type');

        if (!$type) {
            return $this->redirectToRoute('app_project_new_step1', [
                '_locale' => $request->getLocale(),
            ]);
        }

        if ($request->isMethod('POST')) {
            $title = trim($request->request->getString('title'));

            if ($title === '') {
                return $this->render('workshop/projects/new/step2_info.html.twig', [
                    'step'       => 2,
                    'type'       => $type,
                    'error'      => 'Le titre est obligatoire.',
                    'prev_title' => $request->request->getString('title'),
                    'prev_desc'  => $request->request->getString('description'),
                    'prev_genres'=> $request->request->all('genres'),
                    'genres'     => $this->genreRepository->findActiveForType($type),
                ]);
            }

            // Genres sélectionnés (max 3, uniquement slugs valides)
            $selectedSlugs  = array_slice($request->request->all('genres') ?? [], 0, 3);
            $availableSlugs = array_map(
                fn($g) => $g->slug,
                $this->genreRepository->findActiveForType($type)
            );
            $validGenres = array_values(array_intersect($selectedSlugs, $availableSlugs));

            $session->set('funnel_title',       $title);
            $session->set('funnel_description', trim($request->request->getString('description')));
            $session->set('funnel_genres',      $validGenres);

            return $this->redirectToRoute('app_project_new_step3', [
                '_locale' => $request->getLocale(),
            ]);
        }

        return $this->render('workshop/projects/new/step2_info.html.twig', [
            'step'       => 2,
            'type'       => $type,
            'prev_title' => $session->get('funnel_title', ''),
            'prev_desc'  => $session->get('funnel_description', ''),
            'prev_genres'=> $session->get('funnel_genres', []),
            'genres'     => $this->genreRepository->findActiveForType($type),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FUNNEL CRÉATION — ÉTAPE 3 : STRUCTURE + CRÉATION
    // ═══════════════════════════════════════════════════════════════

    public function newStep3(Request $request): Response
    {
        $session = $request->getSession();
        $type    = $session->get('funnel_type');
        $title   = $session->get('funnel_title');

        if (!$type || !$title) {
            $this->addFlash('error', $this->translator->trans('project.session_expired', [], 'flash_messages'));
            return $this->redirectToRoute('app_project_new_step1', [
                '_locale' => $request->getLocale(),
            ]);
        }

        if ($request->isMethod('POST')) {
            $depth      = (int) $request->request->get('depth', 3);
            $levelNames = $request->request->all('level_names');

            if (!in_array($depth, [2, 3, 4], true)) {
                $depth = 3;
            }

            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            $project = new Project();
            $project->setCreatedBy($user);
            $project->title       = $title;
            $project->description = $session->get('funnel_description', '');
            $project->projectType = $type;
            $project->visibility  = Project::VISIBILITY_UNPUBLISHED;
            $project->settings    = ['structure_depth' => $depth];

            $this->em->persist($project);
            $this->em->flush(); // flush pour avoir l'ID avant les configs

            $this->configGenerator->generateConfigsForDepth($project, $type, $depth, $levelNames);

            // Genres sélectionnés pendant l'étape 2
            foreach ($session->get('funnel_genres', []) as $slug) {
                $feature = new ProjectFeature();
                $feature->setProject($project);
                $feature->featureKey = 'genre';
                $feature->value      = $slug;
                $this->em->persist($feature);
            }

            $this->em->flush();

            // Nettoyage session
            $session->remove('funnel_type');
            $session->remove('funnel_title');
            $session->remove('funnel_description');
            $session->remove('funnel_genres');

            $this->activityLog->log('project.create', $user, $project, $project->title);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('project.created', [], 'flash_messages'));

            return $this->redirectToRoute('app_project_show', [
                '_locale' => $request->getLocale(),
                'slug'    => $project->getSlug(),
            ]);
        }

        return $this->render('workshop/projects/new/step3_structure.html.twig', [
            'step'  => 3,
            'type'  => $type,
            'title' => $title,
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

        $tab    = $request->query->getString('tab', 'general');
        $errors = [];

        if ($request->isMethod('POST')) {
            $action = $request->request->getString('_action');

            // ── Sauvegarde infos générales ────────────────────────────────
            if ($action === 'save_general') {
                if (!$this->isCsrfTokenValid('project_settings_' . $project->getId(), $request->request->get('_token'))) {
                    $errors[] = 'Token CSRF invalide.';
                } else {
                    $title = trim($request->request->getString('title'));
                    if ($title === '') {
                        $errors[] = 'Le titre est obligatoire.';
                    } else {
                        // ── Mise à jour du slug si le titre a changé ──────
                        // On mémorise l'ancien titre AVANT de l'écraser,
                        // pour savoir si une régénération de slug est nécessaire.
                        $titleChanged = ($project->title !== $title);

                        $project->title       = $title;
                        $project->description = trim($request->request->getString('description'));

                        // Si le titre a changé, on régénère la partie lisible du slug
                        // tout en conservant le même suffixe aléatoire (identité du projet).
                        // Ex : "god-of-war-motig0ic" → "test-motig0ic"
                        if ($titleChanged) {
                            $project->regenerateSlug($title);
                        }

                        $status = $request->request->getString('status');
                        if (in_array($status, ['draft', 'in_progress', 'completed', 'archived'], true)) {
                            $project->status = $status;
                        }

                        // ── Genres ───────────────────────────────────────
                        // Supprimer les anciens, recréer avec les nouveaux
                        foreach ($project->getProjectFeatures() as $feat) {
                            if ($feat->featureKey === 'genre') {
                                $this->em->remove($feat);
                            }
                        }
                        $availableSlugs = array_map(
                            fn($g) => $g->slug,
                            $this->genreRepository->findActiveForType($project->projectType)
                        );
                        $selectedGenres = array_slice(
                            array_intersect($request->request->all('genres') ?? [], $availableSlugs),
                            0, 3
                        );
                        foreach ($selectedGenres as $slug) {
                            $feature = new ProjectFeature();
                            $feature->setProject($project);
                            $feature->featureKey = 'genre';
                            $feature->value      = $slug;
                            $this->em->persist($feature);
                        }

                        // ── Paramètres manuscrit ──────────────────────────
                        $s = $project->settings;
                        $s['ms_words_per_page']         = max(1, min(999,  (int) $request->request->get('ms_words_per_page',  170)));
                        $s['ms_reading_wpm']            = max(1, min(9999, (int) $request->request->get('ms_reading_wpm',    200)));
                        $s['ms_screen_time_per_page']   = max(1, min(999,  (int) $request->request->get('ms_screen_time_per_page', 60)));
                        $project->settings = $s;

                        $this->em->flush();

                        /** @var \App\Entity\User $user */
                        $user = $this->getUser();
                        $this->activityLog->log('project.edit', $user, $project, $project->title);
                        $this->em->flush();

                        // ── Redirection vers la nouvelle URL ──────────────
                        // Si le titre a changé, getSlug() retourne maintenant
                        // le nouveau slug → la redirection pointe vers la bonne URL.
                        $this->addFlash('success', $this->translator->trans(
                            $titleChanged ? 'project.settings_saved_slug' : 'project.settings_saved',
                            [],
                            'flash_messages'
                        ));
                        return $this->redirectToRoute('app_project_edit', [
                            '_locale' => $request->getLocale(),
                            'slug'    => $project->getSlug(), // ← nouveau slug si titre changé
                            'tab'     => 'general',
                        ]);
                    }
                }
            }

            // ── Sauvegarde des permissions par rôle ──────────────────────
            if ($action === 'save_permissions') {
                if (!$this->isCsrfTokenValid('project_permissions_' . $project->getId(), $request->request->get('_token'))) {
                    $errors[] = 'Token CSRF invalide.';
                } else {
                    // Seul le propriétaire peut modifier les permissions
                    /** @var \App\Entity\User $currentUser */
                    $currentUser = $this->getUser();
                    if ($project->getCreatedBy()->getId() !== $currentUser->getId() && !$this->isGranted('ROLE_ADMIN')) {
                        throw $this->createAccessDeniedException();
                    }

                    foreach (ProjectPermissionService::ROLES as $role) {
                        $resetKey = 'reset_' . $role;
                        if ($request->request->get($resetKey)) {
                            $this->permissionService->resetRole($project, $role);
                        } else {
                            // Les cases cochées arrivent comme tableau via permissions[role][]
                            $submitted = $request->request->all('permissions')[$role] ?? [];
                            $this->permissionService->setRolePermissions($project, $role, $submitted);
                        }
                    }
                    $this->em->flush();
                    $this->addFlash('success', 'Permissions mises à jour.');
                    return $this->redirectToRoute('app_project_edit', [
                        '_locale' => $request->getLocale(),
                        'slug'    => $project->getSlug(),
                        'tab'     => 'permissions',
                    ]);
                }
                $tab = 'permissions';
            }

            // ── Sauvegarde structure (noms des niveaux) ───────────────────
            if ($action === 'save_structure') {
                if (!$this->isCsrfTokenValid('project_structure_' . $project->getId(), $request->request->get('_token'))) {
                    $errors[] = 'Token CSRF invalide.';
                } else {
                    $submitted = $request->request->all('configs');
                    foreach ($project->getProjectTypeConfigs() as $config) {
                        $id = (string) $config->getId();
                        if (!isset($submitted[$id])) {
                            continue;
                        }
                        $singular = trim($submitted[$id]['labelSingular'] ?? '');
                        $plural   = trim($submitted[$id]['labelPlural']   ?? '');
                        if ($singular !== '') {
                            $config->labelSingular = $singular;
                            $config->labelPlural   = $plural !== '' ? $plural : $singular . 's';
                        }
                    }
                    $this->em->flush();
                    $this->addFlash('success', $this->translator->trans('project.structure_updated', [], 'flash_messages'));
                    return $this->redirectToRoute('app_project_edit', [
                        '_locale' => $request->getLocale(),
                        'slug'    => $project->getSlug(),
                        'tab'     => 'structure',
                    ]);
                }
                $tab = 'structure';
            }
        }

        // Configs triées par profondeur pour l'onglet Structure
        $configs = $project->getProjectTypeConfigs()->toArray();
        usort($configs, fn ($a, $b) => $a->depth <=> $b->depth);

        // Genres disponibles pour ce type + genres actuellement sélectionnés
        $availableGenres  = $this->genreRepository->findActiveForType($project->projectType);
        $selectedGenreSlugs = array_map(
            fn($f) => $f->value,
            array_filter(
                $project->getProjectFeatures()->toArray(),
                fn($f) => $f->featureKey === 'genre'
            )
        );

        // Permissions effectives par rôle (pour l'onglet permissions)
        $effectivePerms = [];
        foreach (ProjectPermissionService::ROLES as $role) {
            $effectivePerms[$role] = $this->permissionService->getEffectivePermissions($project, $role);
        }

        return $this->render('workshop/projects/settings.html.twig', [
            'project'             => $project,
            'configs'             => $configs,
            'tab'                 => $tab,
            'errors'              => $errors,
            'available_genres'    => $availableGenres,
            'selected_genre_slugs'=> array_values($selectedGenreSlugs),
            'permGroups'          => ProjectPermissionService::GROUPS,
            'groupIcons'          => ProjectPermissionService::GROUP_ICONS,
            'groupLabels'         => ProjectPermissionService::GROUP_LABELS,
            'permRoles'           => ProjectPermissionService::ROLES,
            'roleLabels'          => ProjectPermissionService::ROLE_LABELS,
            'roleColors'          => ProjectPermissionService::ROLE_COLORS,
            'effectivePerms'      => $effectivePerms,
            'isOwner'             => $project->getCreatedBy()->getId() === $this->getUser()->getId(),
        ]);
    }

    public function delete(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        if ($this->isCsrfTokenValid('delete_project_' . $project->getId(), $request->getPayload()->get('_token'))) {
            $title = $project->title;
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            // Log avant remove (le projet sera SET NULL sur le log via onDelete)
            $this->activityLog->log('project.delete', $user, null, $title);
            $this->em->remove($project);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('project.deleted', [], 'flash_messages'));
        }

        return $this->redirectToRoute('app_project_index');
    }

    // ═══════════════════════════════════════════════════════════════
    // PUBLICATION (visibilité : non publié / privé / en ligne)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Affiche la modal de publication (GET) ou applique la visibilité (POST).
     *
     * GET  → rendu de la modal (via turbo-frame ou redirect)
     * POST → applique la nouvelle visibilité
     */
    public function publish(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->checkProjectAccess($project, 'edit');

        // ── Traitement du formulaire (POST) ──────────────────────────────────
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('publish_' . $project->getId(), $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $newVisibility = $request->request->get('visibility', Project::VISIBILITY_UNPUBLISHED);

            // Validation
            if (!in_array($newVisibility, [
                Project::VISIBILITY_UNPUBLISHED,
                Project::VISIBILITY_PRIVATE,
                Project::VISIBILITY_PUBLIC,
            ], true)) {
                $this->addFlash('error', $this->translator->trans('project.visibility_invalid', [], 'flash_messages'));
                return $this->redirectToRoute('app_project_show', [
                    '_locale' => $request->getLocale(),
                    'slug'    => $project->getSlug(),
                ]);
            }

            // Blocage modération
            if ($newVisibility === Project::VISIBILITY_PUBLIC
                && $project->getModerationStatus() === 'blocked'
            ) {
                $this->addFlash('error', $this->translator->trans('project.moderation_blocked', [], 'flash_messages'));
                return $this->redirectToRoute('app_project_show', [
                    '_locale' => $request->getLocale(),
                    'slug'    => $project->getSlug(),
                ]);
            }

            // ── Sélection des éléments à publier (tous types) ────────────────
            if ($newVisibility === Project::VISIBILITY_PUBLIC) {
                $selectedIds = $request->request->all('element_ids') ?: [];
                foreach ($project->getScenarioElements() as $element) {
                    if ($element->getDepth() === 1) {
                        // Les éléments racine sont publics selon la sélection
                        $element->isPublic = in_array((string) $element->getId(), $selectedIds, true);
                    } else {
                        // Les enfants suivent leur parent racine
                        $root = $element;
                        while ($root->getParent() !== null) {
                            $root = $root->getParent();
                        }
                        $element->isPublic = in_array((string) $root->getId(), $selectedIds, true);
                    }
                }
            } else {
                // Projet non public → tout dépublier
                foreach ($project->getScenarioElements() as $element) {
                    $element->isPublic = false;
                }
            }

            $project->setVisibility($newVisibility);
            $this->em->flush();

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->activityLog->log('project.publish', $user, $project, $project->title);
            $this->em->flush();

            $labels = [
                Project::VISIBILITY_PUBLIC      => 'project.set_public',
                Project::VISIBILITY_PRIVATE     => 'project.set_private',
                Project::VISIBILITY_UNPUBLISHED => 'project.set_unpublished',
            ];

            $this->addFlash(
                'success',
                $this->translator->trans($labels[$newVisibility], [], 'flash_messages')
            );
        }

        return $this->redirectToRoute('app_project_show', [
            '_locale' => $request->getLocale(),
            'slug'    => $project->getSlug(),
        ]);
    }

    /**
     * Alias rétro-compatible (ancienne route app_project_toggle_visibility).
     * Redirige vers la logique publish.
     *
     * @deprecated Utilisé uniquement pour ne pas casser les anciens bookmarks.
     */
    public function toggleVisibility(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        return $this->publish($request, $project);
    }
}
