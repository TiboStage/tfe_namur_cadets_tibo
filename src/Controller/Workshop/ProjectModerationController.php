<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Repository\ActivityLogRepository;
use App\Service\ProjectPermissionService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProjectModerationController extends AbstractController
{
    use ProjectAccessTrait;

    public function __construct(
        private readonly ActivityLogRepository   $activityLogRepository,
        private readonly ProjectPermissionService $permissionService,
    ) {}

    public function index(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project,
    ): Response {
        // Seul le propriétaire (ou admin) accède à la modération
        $this->checkProjectAccess($project, 'delete');

        /** @var \App\Entity\User $owner */
        $owner = $project->getCreatedBy();

        // Construire les stats par membre
        $membersData = [];

        foreach ($project->getProjectMembers() as $member) {
            $user      = $member->getUser();
            $recentLog = $this->activityLogRepository->findByUserAndProject(
                $user->getId(),
                $project->getId(),
                5
            );

            // Compter les actions par type
            $allLogs = $this->activityLogRepository->findByUserAndProject(
                $user->getId(),
                $project->getId(),
                1000
            );

            $actionCounts = [];
            foreach ($allLogs as $log) {
                $actionCounts[$log->getAction()] = ($actionCounts[$log->getAction()] ?? 0) + 1;
            }

            $membersData[] = [
                'member'        => $member,
                'user'          => $user,
                'recentLog'     => $recentLog,
                'actionCounts'  => $actionCounts,
                'totalActions'  => count($allLogs),
                'effectivePerms'=> $this->permissionService->getEffectivePermissions($project, $member->role),
                'isCustomized'  => $this->permissionService->isCustomized($project, $member->role),
            ];
        }

        return $this->render('workshop/projects/moderation.html.twig', [
            'project'     => $project,
            'owner'       => $owner,
            'membersData' => $membersData,
            'permGroups'  => ProjectPermissionService::GROUPS,
            'groupIcons'  => ProjectPermissionService::GROUP_ICONS,
            'groupLabels' => ProjectPermissionService::GROUP_LABELS,
            'roleLabels'  => ProjectPermissionService::ROLE_LABELS,
            'roleColors'  => ProjectPermissionService::ROLE_COLORS,
        ]);
    }
}
