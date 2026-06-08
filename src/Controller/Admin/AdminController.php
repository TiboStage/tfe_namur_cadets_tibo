<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Panneau d'administration — réservé à ROLE_ADMIN.
 *
 * Sections :
 *   - Dashboard (stats globales)
 *   - Utilisateurs (liste, détail, ban/unban, rôles)
 *   - Projets (liste, détail)
 *   - Modération (projets signalés)
 *   - Configuration (paramètres plateforme)
 */
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository    $userRepo,
        private readonly ProjectRepository $projectRepo,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface    $translator,
    ) {}

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users_total'   => $this->userRepo->countAll(),
                'users_new'     => $this->userRepo->countNew(30),
                'users_banned'  => $this->userRepo->countBanned(),
                'projects_total'  => $this->projectRepo->countAll(),
                'projects_public' => $this->projectRepo->countPublic(),
                'reports'         => $this->projectRepo->countFlagged(),
            ],
            'recent_users'    => array_slice($this->userRepo->findAllForAdmin(), 0, 5),
            'flagged_projects' => array_slice($this->projectRepo->findFlagged(), 0, 5),
        ]);
    }

    // ─── Utilisateurs ─────────────────────────────────────────────────────────

    public function users(Request $request): Response
    {
        $q = $request->query->getString('q');
        $users = $q
            ? $this->userRepo->searchAdmin($q)
            : $this->userRepo->findAllForAdmin();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'q'     => $q,
        ]);
    }

    public function userShow(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user'     => $user,
            'projects' => $this->projectRepo->findBy(['createdBy' => $user], ['createdAt' => 'DESC']),
        ]);
    }

    /**
     * Banir / débanir un utilisateur.
     */
    public function userBan(Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('admin_ban_' . $user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), '_locale' => $request->getLocale()]);
        }

        $user->setIsBanned(!$user->isIsBanned());
        $this->em->flush();

        $this->addFlash('success', $user->isIsBanned()
            ? "Utilisateur {$user->getUsername()} banni."
            : "Utilisateur {$user->getUsername()} débanni."
        );

        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), '_locale' => $request->getLocale()]);
    }

    /**
     * Modifier les rôles d'un utilisateur.
     */
    public function userRoles(Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('admin_roles_' . $user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), '_locale' => $request->getLocale()]);
        }

        // Empêcher de modifier son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier vos propres rôles.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), '_locale' => $request->getLocale()]);
        }

        $allowed = ['ROLE_USER', 'ROLE_MODO', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
        $newRoles = array_intersect(
            $request->request->all('roles') ?: [],
            $allowed
        );

        // ROLE_USER est toujours présent (géré par getRoles())
        $newRoles = array_values(array_diff($newRoles, ['ROLE_USER']));
        $user->setRoles($newRoles);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('admin.user_roles_updated', ['%username%' => $user->getUsername()], 'flash_messages'));
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), '_locale' => $request->getLocale()]);
    }

    // ─── Projets ──────────────────────────────────────────────────────────────

    public function projects(Request $request): Response
    {
        $filter = $request->query->getString('filter', 'all');

        $projects = match ($filter) {
            'flagged' => $this->projectRepo->findFlagged(),
            'public'  => $this->projectRepo->findBy(['visibility' => 'public'], ['createdAt' => 'DESC']),
            default   => $this->projectRepo->findAllForAdmin(),
        };

        return $this->render('admin/projects/index.html.twig', [
            'projects' => $projects,
            'filter'   => $filter,
            'counts'   => [
                'all'     => $this->projectRepo->countAll(),
                'public'  => $this->projectRepo->countPublic(),
                'flagged' => $this->projectRepo->countFlagged(),
            ],
        ]);
    }

    public function projectShow(Project $project): Response
    {
        return $this->render('admin/projects/show.html.twig', [
            'project' => $project,
        ]);
    }

    // ─── Modération ───────────────────────────────────────────────────────────

    public function moderation(): Response
    {
        return $this->render('admin/moderation/index.html.twig', [
            'flagged' => $this->projectRepo->findFlagged(),
        ]);
    }

    /**
     * Changer le statut de modération d'un projet.
     */
    public function moderationStatus(Request $request, Project $project): Response
    {
        if (!$this->isCsrfTokenValid('mod_status_' . $project->getId(), $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_moderation', ['_locale' => $request->getLocale()]);
        }

        $status = $request->request->getString('status');
        $allowed = ['clear', 'warning', 'blocked', 'approved'];

        if (in_array($status, $allowed)) {
            $project->setModerationStatus($status);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('admin.project_status_updated', ['%title%' => $project->getTitle(), '%status%' => $status], 'flash_messages'));
        }

        return $this->redirectToRoute('admin_moderation', ['_locale' => $request->getLocale()]);
    }

    // ─── Configuration ────────────────────────────────────────────────────────

    public function config(): Response
    {
        return $this->render('admin/config.html.twig');
    }
}
