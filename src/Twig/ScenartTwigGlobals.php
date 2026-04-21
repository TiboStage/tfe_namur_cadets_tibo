<?php

namespace App\Twig;

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
        private readonly ProjectRepository $projectRepository,
        private readonly Security $security,
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        return [
            'projects_count' => $user
                ? $this->projectRepository->count(['createdBy' => $user])
                : 0,
        ];
    }
}
