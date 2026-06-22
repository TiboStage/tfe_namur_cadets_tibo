<?php

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Trait partagé par tous les controllers Workshop.
 * Retourne une 404 au lieu d'une 403 pour ne pas révéler
 * l'existence des ressources d'autres utilisateurs.
 *
 * Attributs acceptés par checkProjectAccess() :
 *   'view'       — tout membre + projets publics (lecteurs inclus)
 *   'annotate'   — tout membre authentifié (lecteur peut créer des notes)
 *   'contribute' — contributeur, modérateur, propriétaire (écriture de contenu)
 *   'manage'     — modérateur, propriétaire (paramètres, visibilité)
 *   'delete'     — propriétaire uniquement
 */
trait ProjectAccessTrait
{
    protected function checkProjectAccess(Project $project, string $attribute = 'view'): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $hasAccess = match ($attribute) {
            'view'       => $this->canView($project, $user),
            'annotate'   => $this->canAnnotate($project, $user),
            'contribute' => $this->canContribute($project, $user),
            'manage'     => $this->canManage($project, $user),
            'delete'     => $this->canDelete($project, $user),
            default      => false,
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
        return !$this->canContribute($project, $user);
    }

    // ─── Checks internes ─────────────────────────────────────────────────────

    private function canView(Project $project, User $user): bool
    {
        if ($project->isPublic()) {
            return true;
        }

        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId() && $member->isActive()) {
                return true;
            }
        }

        return false;
    }

    /** Tout membre authentifié du projet (lecteur inclus) peut annoter. */
    private function canAnnotate(Project $project, User $user): bool
    {
        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()->getId() === $user->getId() && $member->isActive()) {
                return true;
            }
        }

        return false;
    }

    /** Contributeur, modérateur et propriétaire peuvent écrire du contenu. */
    private function canContribute(Project $project, User $user): bool
    {
        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if (
                $member->getUser()->getId() === $user->getId()
                && $member->isActive()
                && in_array($member->getRole(), [ProjectMember::ROLE_CONTRIBUTOR, ProjectMember::ROLE_MODERATOR], true)
            ) {
                return true;
            }
        }

        return false;
    }

    /** Seuls le modérateur et le propriétaire peuvent gérer le projet. */
    private function canManage(Project $project, User $user): bool
    {
        if ($project->getCreatedBy()->getId() === $user->getId()) {
            return true;
        }

        foreach ($project->getProjectMembers() as $member) {
            if (
                $member->getUser()->getId() === $user->getId()
                && $member->isActive()
                && $member->getRole() === ProjectMember::ROLE_MODERATOR
            ) {
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
