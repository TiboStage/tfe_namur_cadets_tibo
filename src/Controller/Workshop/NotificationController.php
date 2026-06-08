<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── Page liste complète ──────────────────────────────────────────────────

    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Marquer toutes comme lues à l'ouverture de la page
        $this->notificationRepository->markAllAsRead($user->getId());

        $notifications = $this->notificationRepository->findAllByUser($user->getId(), 50);

        return $this->render('workshop/notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    // ── Dropdown AJAX (5 dernières + count) ─────────────────────────────────

    public function dropdown(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $unread = $this->notificationRepository->findUnreadByUser($user->getId());
        $recent = $this->notificationRepository->findAllByUser($user->getId(), 8);

        $items = array_map(fn(Notification $n) => [
            'id'        => $n->getId(),
            'content'   => $n->content,
            'type'      => $n->type,
            'link'      => $n->getLink(),
            'isRead'    => $n->isRead,
            'createdAt' => $n->getCreatedAt()->format('d/m/Y H:i'),
        ], $recent);

        return $this->json([
            'unreadCount' => count($unread),
            'items'       => $items,
        ]);
    }

    // ── Marquer UNE notification comme lue (AJAX) ───────────────────────────

    public function markRead(
        Request $request,
        #[MapEntity(id: 'id')] Notification $notification
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenValid('notif_read', $request->request->get('_token'))) {
            return $this->json(['error' => 'CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $notification->markAsRead();
        $this->em->flush();

        return $this->json(['success' => true, 'id' => $notification->getId()]);
    }

    // ── Marquer TOUTES comme lues (AJAX) ────────────────────────────────────

    public function markAllRead(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('notif_read_all', $request->request->get('_token'))) {
            return $this->json(['error' => 'CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->notificationRepository->markAllAsRead($user->getId());

        return $this->json(['success' => true]);
    }
}
