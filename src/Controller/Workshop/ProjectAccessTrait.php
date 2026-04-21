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
    private function checkProjectAccess(Project $project, string $attribute = 'view'): void
    {
        /** @var User $user */
        $user = $this->getUser();

        // Super admin — accès total
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
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

    private function canView(Project $project, User $user): bool
    {
        if ($project->isPublic()) {
            return true;
        }

        if ($project->getCreatedBy() === $user) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser() === $user) {
                return true;
            }
        }

        return false;
    }

    private function canEdit(Project $project, User $user): bool
    {
        if ($project->getCreatedBy() === $user) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser() === $user && in_array($member->getRole(), ['editor', 'lead'])) {
                return true;
            }
        }

        return false;
    }

    private function canDelete(Project $project, User $user): bool
    {
        return $project->getCreatedBy() === $user;
    }
}
