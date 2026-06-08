<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Report;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\ProjectRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Endpoint de signalement — projet, commentaire ou utilisateur.
 *
 * Répond en JSON si la requête est XHR (modal Stimulus),
 * sinon redirige avec un flash.
 */
#[IsGranted('ROLE_USER')]
class ReportController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReportRepository       $reportRepository,
        private readonly ProjectRepository      $projectRepository,
        private readonly CommentRepository      $commentRepository,
        private readonly TranslatorInterface    $translator,
    ) {}

    public function report(Request $request, string $type, int $id): Response
    {
        $isAjax = $request->isXmlHttpRequest();

        // ── Validation du type ────────────────────────────────────────────────
        if (!in_array($type, [Report::TYPE_PROJECT, Report::TYPE_COMMENT, Report::TYPE_USER], true)) {
            if ($isAjax) {
                return new JsonResponse(['error' => 'Type de signalement invalide.'], 400);
            }
            throw $this->createNotFoundException('Type invalide.');
        }

        // ── CSRF ──────────────────────────────────────────────────────────────
        if (!$this->isCsrfTokenValid('report_' . $type . '_' . $id, $request->request->getString('_token'))) {
            if ($isAjax) {
                return new JsonResponse(['error' => 'Token de sécurité invalide.'], 403);
            }
            $this->addFlash('error', $this->translator->trans('csrf.invalid', [], 'flash_messages'));
            return $this->redirect($request->headers->get('referer') ?? '/');
        }

        /** @var User $user */
        $user = $this->getUser();

        // ── Anti-doublon ──────────────────────────────────────────────────────
        if ($this->reportRepository->hasAlreadyReported($user->getId(), $type, $id)) {
            if ($isAjax) {
                return new JsonResponse(['error' => 'Vous avez déjà signalé cet élément.'], 409);
            }
            $this->addFlash('warning', $this->translator->trans('comment.already_reported', [], 'flash_messages'));
            return $this->redirect($request->headers->get('referer') ?? '/');
        }

        // ── Validation de la raison ───────────────────────────────────────────
        $reason = $request->request->getString('reason');
        if (!array_key_exists($reason, Report::REASONS)) {
            if ($isAjax) {
                return new JsonResponse(['error' => 'Raison de signalement invalide.'], 400);
            }
            $this->addFlash('error', $this->translator->trans('comment.reason_invalid', [], 'flash_messages'));
            return $this->redirect($request->headers->get('referer') ?? '/');
        }

        // ── Création du signalement ───────────────────────────────────────────
        $report = new Report();
        $report->setReporter($user);
        $report->targetType  = $type;
        $report->reason      = $reason;
        $report->description = ($request->request->getString('description') ?: null);

        // Associer la cible selon son type
        match ($type) {
            Report::TYPE_PROJECT => $report->setTargetProject($this->projectRepository->find($id)),
            Report::TYPE_COMMENT => $report->setTargetComment($this->commentRepository->find($id)),
            Report::TYPE_USER    => $report->setTargetUser($this->em->find(User::class, $id)),
        };

        $this->em->persist($report);
        $this->em->flush();

        if ($isAjax) {
            return new JsonResponse(['success' => true, 'message' => 'Signalement envoyé. Merci pour votre contribution !']);
        }

        $this->addFlash('success', $this->translator->trans('comment.report_submitted', [], 'flash_messages'));
        return $this->redirect($request->headers->get('referer') ?? '/');
    }
}
