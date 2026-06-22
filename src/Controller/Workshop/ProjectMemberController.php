<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Service\ProjectPermissionService;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gestion des collaborateurs d'un projet.
 *
 * Toutes les actions nécessitent d'être propriétaire du projet.
 */
#[IsGranted('ROLE_USER')]
final class ProjectMemberController extends AbstractController
{
    private const VALID_ROLES = [ProjectMember::ROLE_READER, ProjectMember::ROLE_CONTRIBUTOR, ProjectMember::ROLE_MODERATOR];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository          $userRepository,
        private readonly NotificationService     $notificationService,
        private readonly TranslatorInterface     $translator,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // INVITER un membre
    // ═══════════════════════════════════════════════════════════════

    public function invite(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        $this->denyUnlessOwner($project);

        if (!$this->isCsrfTokenValid('member_invite_' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('member.csrf_invalid', [], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        $username = trim((string) $request->request->get('username', ''));
        $role     = $request->request->get('role', 'contributor');

        // Validation du rôle
        if (!in_array($role, self::VALID_ROLES, true)) {
            $this->addFlash('error', $this->translator->trans('member.role_invalid', [], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        // Trouver l'utilisateur cible
        $target = $this->userRepository->findOneBy(['username' => $username]);
        if ($target === null) {
            $this->addFlash('error', $this->translator->trans('member.user_not_found', ['%username%' => $username], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        // Impossible d'inviter le propriétaire lui-même
        if ($target->getId() === $project->getCreatedBy()?->getId()) {
            $this->addFlash('error', $this->translator->trans('member.already_owner', [], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        // Vérifier qu'il n'est pas déjà membre
        foreach ($project->getProjectMembers() as $existing) {
            if ($existing->getUser()?->getId() === $target->getId()) {
                $this->addFlash('error', $this->translator->trans('member.already_collaborator', ['%username%' => $username], 'flash_messages'));
                return $this->redirectToSettings($request, $project);
            }
        }

        // Créer le membre (en attente d'acceptation)
        $member = new ProjectMember();
        $member->setProject($project);
        $member->setUser($target);
        $member->role   = $role;
        $member->status = ProjectMember::STATUS_PENDING;
        $this->em->persist($member);

        // Notification à l'invité avec lien vers la page de réponse
        $roleLabel = $this->roleLabel($role);
        $this->notificationService->notify(
            user:    $target,
            content: sprintf(
                '« %s » vous a invité(e) comme %s sur le projet « %s ».',
                $project->getCreatedBy()?->getUsername() ?? 'Quelqu\'un',
                $roleLabel,
                $project->title,
            ),
            type:    NotificationService::TYPE_INVITATION,
            link:    $this->generateUrl('app_member_invitation_view', [
                '_locale' => $request->getLocale(),
                'slug'    => $project->getSlug(),
            ]),
            flush:   false,
        );

        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('member.invited', ['%username%' => $username, '%role%' => $roleLabel], 'flash_messages'));
        return $this->redirectToSettings($request, $project);
    }

    // ═══════════════════════════════════════════════════════════════
    // VOIR / ACCEPTER / DÉCLINER une invitation
    // ═══════════════════════════════════════════════════════════════

    public function invitationView(
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        /** @var User $user */
        $user   = $this->getUser();
        $member = $this->findMember($project, $user->getId());

        if ($member === null || !$member->isPending()) {
            throw $this->createNotFoundException('Aucune invitation en attente pour ce projet.');
        }

        return $this->render('workshop/invitation.html.twig', [
            'project' => $project,
            'member'  => $member,
        ]);
    }

    public function acceptInvitation(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('invite_accept_' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('member.csrf_invalid', [], 'flash_messages'));
            return $this->redirectToRoute('app_member_invitation_view', ['_locale' => $request->getLocale(), 'slug' => $project->getSlug()]);
        }

        $member = $this->findMember($project, $user->getId());
        if ($member === null || !$member->isPending()) {
            throw $this->createNotFoundException('Aucune invitation en attente pour ce projet.');
        }

        $member->status = ProjectMember::STATUS_ACTIVE;
        $this->em->flush();

        $this->addFlash('success', sprintf('Vous avez rejoint le projet « %s ». Bienvenue !', $project->title));
        return $this->redirectToRoute('app_project_show', ['_locale' => $request->getLocale(), 'slug' => $project->getSlug()]);
    }

    public function declineInvitation(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('invite_decline_' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('member.csrf_invalid', [], 'flash_messages'));
            return $this->redirectToRoute('app_member_invitation_view', ['_locale' => $request->getLocale(), 'slug' => $project->getSlug()]);
        }

        $member = $this->findMember($project, $user->getId());
        if ($member === null || !$member->isPending()) {
            throw $this->createNotFoundException('Aucune invitation en attente pour ce projet.');
        }

        $this->em->remove($member);
        $this->em->flush();

        $this->addFlash('success', 'Invitation déclinée.');
        return $this->redirectToRoute('app_workshop_dashboard', ['_locale' => $request->getLocale()]);
    }

    // ═══════════════════════════════════════════════════════════════
    // CHANGER LE RÔLE d'un membre
    // ═══════════════════════════════════════════════════════════════

    public function updateRole(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project,
        int $userId
    ): Response {
        $this->denyUnlessOwner($project);

        if (!$this->isCsrfTokenValid('member_role_' . $project->getId() . '_' . $userId, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('member.csrf_invalid', [], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        $newRole = $request->request->get('role', 'contributor');
        if (!in_array($newRole, self::VALID_ROLES, true)) {
            $this->addFlash('error', $this->translator->trans('member.role_invalid', [], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        $member = $this->findMember($project, $userId);
        if ($member === null) {
            throw $this->createNotFoundException();
        }

        $oldRole = $member->role;
        $member->role = $newRole;

        // Notification si le rôle change réellement
        if ($oldRole !== $newRole && $member->getUser() !== null) {
            $this->notificationService->notify(
                user:    $member->getUser(),
                content: sprintf(
                    'Votre rôle sur « %s » a été modifié : vous êtes maintenant %s.',
                    $project->title,
                    $this->roleLabel($newRole),
                ),
                type:    NotificationService::TYPE_ROLE_CHANGED,
                link:    $this->generateUrl('app_project_show', [
                    '_locale' => $request->getLocale(),
                    'slug'    => $project->getSlug(),
                ]),
                flush:   false,
            );
        }

        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('member.role_updated', [], 'flash_messages'));
        return $this->redirectToSettings($request, $project);
    }

    // ═══════════════════════════════════════════════════════════════
    // RETIRER un membre
    // ═══════════════════════════════════════════════════════════════

    public function remove(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project,
        int $userId
    ): Response {
        $this->denyUnlessOwner($project);

        if (!$this->isCsrfTokenValid('member_remove_' . $project->getId() . '_' . $userId, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('member.csrf_invalid', [], 'flash_messages'));
            return $this->redirectToSettings($request, $project);
        }

        $member = $this->findMember($project, $userId);
        if ($member === null) {
            throw $this->createNotFoundException();
        }

        $targetUser = $member->getUser();
        $this->em->remove($member);

        // Notification au membre retiré
        if ($targetUser !== null) {
            $this->notificationService->notify(
                user:    $targetUser,
                content: sprintf(
                    'Vous avez été retiré(e) du projet « %s ».',
                    $project->title,
                ),
                type:    NotificationService::TYPE_REMOVED,
                link:    null,
                flush:   false,
            );
        }

        $this->em->flush();

        $name = $targetUser?->getUsername() ?? 'Ce collaborateur';
        $this->addFlash('success', $this->translator->trans('member.removed', ['%name%' => $name], 'flash_messages'));
        return $this->redirectToSettings($request, $project);
    }

    // ═══════════════════════════════════════════════════════════════
    // QUITTER un projet (auto-retrait)
    // ═══════════════════════════════════════════════════════════════

    public function leave(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($project->getCreatedBy()?->getId() === $user->getId()) {
            $this->addFlash('error', 'Le propriétaire ne peut pas quitter son propre projet.');
            return $this->redirectToRoute('app_project_show', ['_locale' => $request->getLocale(), 'slug' => $project->getSlug()]);
        }

        if (!$this->isCsrfTokenValid('member_leave_' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('member.csrf_invalid', [], 'flash_messages'));
            return $this->redirectToRoute('app_project_show', ['_locale' => $request->getLocale(), 'slug' => $project->getSlug()]);
        }

        $member = $this->findMember($project, $user->getId());
        if ($member === null) {
            throw $this->createNotFoundException('Vous n\'êtes pas membre de ce projet.');
        }

        $this->em->remove($member);
        $this->em->flush();

        $this->addFlash('success', sprintf('Vous avez quitté le projet « %s ».', $project->title));
        return $this->redirectToRoute('app_project_index', ['_locale' => $request->getLocale()]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers privés
    // ═══════════════════════════════════════════════════════════════

    private function denyUnlessOwner(Project $project): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($project->getCreatedBy()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Seul le propriétaire peut gérer les collaborateurs.');
        }
    }

    private function findMember(Project $project, int $userId): ?ProjectMember
    {
        foreach ($project->getProjectMembers() as $member) {
            if ($member->getUser()?->getId() === $userId) {
                return $member;
            }
        }

        return null;
    }

    private function redirectToSettings(Request $request, Project $project): Response
    {
        return $this->redirectToRoute('app_project_edit', [
            '_locale' => $request->getLocale(),
            'slug'    => $project->getSlug(),
        ]);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            ProjectMember::ROLE_MODERATOR   => 'modérateur',
            ProjectMember::ROLE_CONTRIBUTOR => 'contributeur',
            ProjectMember::ROLE_READER      => 'lecteur',
            default                         => $role,
        };
    }
}
