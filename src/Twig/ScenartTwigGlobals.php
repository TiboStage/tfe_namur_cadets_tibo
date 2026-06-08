<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use App\Repository\ProjectMemberRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Variables globales disponibles dans tous les templates Twig.
 *
 * Utilisation dans n'importe quel template :
 *   {{ projects_count }}
 */
class ScenartTwigGlobals extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ProjectRepository      $projectRepository,
        private readonly ProjectMemberRepository $projectMemberRepository,
        private readonly NotificationRepository  $notificationRepository,
        private readonly Security                $security,
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        $notifCount = 0;
        if ($user !== null) {
            $notifCount = $this->notificationRepository->countUnreadByUser($user->getId());
        }

        $projectsCount = 0;
        if ($user !== null) {
            $projectsCount = $this->projectRepository->count(['createdBy' => $user])
                           + $this->projectMemberRepository->countByUser($user->getId());
        }

        return [
            'projects_count'     => $projectsCount,
            'unread_notif_count' => $notifCount,
        ];
    }
}
