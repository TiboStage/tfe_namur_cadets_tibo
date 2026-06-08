<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Trait partagé par tous les controllers Workshop.
 * Retourne une 404 au lieu d'une 403 pour ne pas révéler
 * l'existence des ressources d'autres utilisateurs.
 */
trait ProjectAccessTrait
{
    protected function checkProjectAccess(Project $project, string $attribute = 'view'): void
    {
        /** @var User $user */
        $user = $this->getUser();

        // Admin+ — accès total (respecte la hiérarchie des rôles)
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $hasAccess = match ($attribute) {
            'view'   => $this->canView($project, $user),
            'edit'   => $this->canEdit($project, $user),
            'delete' => $this->canDelete($project, $user),
            default  => false,
        };

        if (!$hasAccess) {
            throw new NotFoundHttpException();
        }
    }

    protected function isReadOnly(Project $project): bool
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return false;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId()) {
                return false;
            }
        }

        return true;
    }

    private function canView(Project $project, User $user): bool
    {
        if ($project->isPublic()) {
            return true;
        }

        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }

    private function canEdit(Project $project, User $user): bool
    {
        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId() && in_array($member->getRole(), ['editor', 'lead'])) {
                return true;
            }
        }

        return false;
    }

    private function canDelete(Project $project, User $user): bool
    {
        return $project->getCreatedBy()->getId() === $user->getId();
    }
}
