<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée et persiste des notifications in-app.
 *
 * Usage :
 *   $notificationService->notify(
 *       user:    $invitedUser,
 *       content: "Vous avez été invité au projet « Mon Film »",
 *       type:    'invitation',
 *       link:    '/fr/atelier/projets/mon-film-abc12345'
 *   );
 */
final class NotificationService
{
    // Types disponibles (pour les icônes / couleurs en Twig)
    public const TYPE_INFO             = 'info';
    public const TYPE_INVITATION       = 'invitation';
    public const TYPE_REMOVED          = 'removed';
    public const TYPE_ROLE_CHANGED     = 'role_changed';
    public const TYPE_PROJECT_PUBLISHED = 'project_published';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Crée une notification pour un utilisateur.
     *
     * @param bool $flush Flusher immédiatement (true par défaut).
     *                    Passer false si on est dans une transaction plus large.
     */
    public function notify(
        User    $user,
        string  $content,
        string  $type  = self::TYPE_INFO,
        ?string $link  = null,
        bool    $flush = true,
    ): Notification {
        $notif = new Notification();
        $notif->setUser($user);
        $notif->content = $content;
        $notif->type    = $type;
        $notif->setLink($link);

        $this->em->persist($notif);

        if ($flush) {
            $this->em->flush();
        }

        return $notif;
    }

    /**
     * Notifie tous les membres d'un projet (sauf l'initiateur).
     *
     * @param User[] $members
     */
    public function notifyMany(
        array   $members,
        string  $content,
        string  $type  = self::TYPE_INFO,
        ?string $link  = null,
    ): void {
        foreach ($members as $member) {
            $this->notify($member, $content, $type, $link, false);
        }

        $this->em->flush();
    }
}
