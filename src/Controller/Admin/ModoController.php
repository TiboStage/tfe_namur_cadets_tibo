<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Entity\Report;
use App\Repository\CommentRepository;
use App\Repository\ProjectRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Panneau modérateur — accessible à ROLE_MODO (et donc aussi ROLE_ADMIN).
 *
 * Les modérateurs peuvent :
 *   - Voir le tableau de bord de modération
 *   - Consulter et traiter les signalements (entité Report)
 *   - Modérer les commentaires (masquer / supprimer)
 *   - Gérer le statut de modération des projets (warning / blocked / clear)
 */
#[IsGranted('ROLE_MODO')]
class ModoController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository      $projectRepo,
        private readonly ReportRepository       $reportRepository,
        private readonly CommentRepository      $commentRepository,
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface    $translator,
    ) {}

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(): Response
    {
        return $this->render('modo/dashboard.html.twig', [
            'flagged'               => $this->projectRepo->findFlagged(),
            'count_flagged'         => $this->projectRepo->countFlagged(),
            'count_total'           => $this->projectRepo->countAll(),
            'count_pending_reports' => $this->reportRepository->countPending(),
            'recent_reports'        => $this->reportRepository->findPending(),
            'count_comments'        => $this->commentRepository->countAll(),
        ]);
    }

    // ─── Signalements projets (ancien système flags) ──────────────────────────

    public function reports(): Response
    {
        return $this->render('modo/reports.html.twig', [
            'flagged' => $this->projectRepo->findFlagged(),
        ]);
    }

    public function projectShow(Project $project): Response
    {
        return $this->render('modo/project_show.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * Change le statut de modération d'un projet depuis le panel modo.
     */
    public function projectReview(Request $request, Project $project): Response
    {
        if (!$this->isCsrfTokenValid('modo_review_' . $project->getId(), $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('modo_reports', ['_locale' => $request->getLocale()]);
        }

        $status  = $request->request->getString('status');
        $allowed = ['clear', 'warning', 'blocked'];

        if (in_array($status, $allowed)) {
            $project->setModerationStatus($status);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('moderation.project_status_updated', ['%title%' => $project->getTitle(), '%status%' => $status], 'flash_messages'));
        }

        return $this->redirectToRoute('modo_reports', ['_locale' => $request->getLocale()]);
    }

    // ─── Signalements système (entité Report) ─────────────────────────────────

    public function reportList(): Response
    {
        return $this->render('modo/report_list.html.twig', [
            'reports'       => $this->reportRepository->findAllForModo(),
            'pending_count' => $this->reportRepository->countPending(),
        ]);
    }

    public function reportShow(int $id): Response
    {
        $report = $this->reportRepository->find($id);
        if ($report === null) {
            throw $this->createNotFoundException('Signalement introuvable.');
        }

        return $this->render('modo/report_show.html.twig', [
            'report' => $report,
        ]);
    }

    public function reportReview(Request $request, int $id): Response
    {
        $report = $this->reportRepository->find($id);
        if ($report === null) {
            throw $this->createNotFoundException('Signalement introuvable.');
        }

        if (!$this->isCsrfTokenValid('report_review_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('modo_report_list', ['_locale' => $request->getLocale()]);
        }

        $status  = $request->request->getString('status');
        $allowed = [Report::STATUS_REVIEWED, Report::STATUS_DISMISSED, Report::STATUS_ACTIONED];

        if (in_array($status, $allowed, true)) {
            $report->status = $status;
            $report->setReviewedBy($this->getUser());
            $report->setReviewedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('moderation.report_status_updated', [], 'flash_messages'));
        }

        // Redirige vers la liste sauf si on venait d'une page de détail
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/modo/signalements/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('modo_report_list', ['_locale' => $request->getLocale()]);
    }

    // ─── Commentaires ─────────────────────────────────────────────────────────

    public function comments(): Response
    {
        return $this->render('modo/comments.html.twig', [
            'comments'       => $this->commentRepository->findAllForModo(),
            'count_comments' => $this->commentRepository->countAll(),
        ]);
    }

    public function commentModerate(Request $request, int $id): Response
    {
        $comment = $this->commentRepository->find($id);
        if ($comment === null) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        if (!$this->isCsrfTokenValid('comment_moderate_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('modo_comments', ['_locale' => $request->getLocale()]);
        }

        $action = $request->request->getString('action'); // hide | show | delete

        match ($action) {
            'hide'   => (function () use ($comment) {
                $comment->status = 'hidden';
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('moderation.comment_hidden', [], 'flash_messages'));
            })(),
            'show'   => (function () use ($comment) {
                $comment->status = 'visible';
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('moderation.comment_shown', [], 'flash_messages'));
            })(),
            'delete' => (function () use ($comment) {
                $this->em->remove($comment);
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('moderation.comment_deleted', [], 'flash_messages'));
            })(),
            default  => $this->addFlash('warning', $this->translator->trans('moderation.unknown_action', [], 'flash_messages')),
        };

        return $this->redirectToRoute('modo_comments', ['_locale' => $request->getLocale()]);
    }
}
