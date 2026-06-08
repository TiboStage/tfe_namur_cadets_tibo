<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Persiste la locale de l'utilisateur dans un cookie HTTP (_locale, 1 an).
 *
 * Pourquoi : Symfony détermine la locale depuis l'URL (/{_locale}/…) à chaque
 * requête. Sans cookie persistant, un nouvel onglet ou une URL racine ne sait
 * pas quelle langue l'utilisateur préfère. Ce subscriber écrit/rafraîchit le
 * cookie à chaque réponse principale quand la locale change.
 *
 * Lecture du cookie : à implémenter dans un kernel.request subscriber si l'on
 * veut rediriger "/" vers la langue préférée — hors périmètre pour l'instant.
 */
class LocaleCookieSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME    = '_locale';
    private const COOKIE_LIFETIME = 31_536_000; // 1 an en secondes

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Sous-requêtes (ESI, fragments Symfony) : on ignore
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale  = $request->getLocale();

        // Rien à faire si le cookie est déjà à jour
        if ($request->cookies->get(self::COOKIE_NAME) === $locale) {
            return;
        }

        $event->getResponse()->headers->setCookie(
            new Cookie(
                name    : self::COOKIE_NAME,
                value   : $locale,
                expire  : time() + self::COOKIE_LIFETIME,
                path    : '/',
                domain  : null,
                secure  : $request->isSecure(),   // true en HTTPS (prod), false en dev
                httpOnly: true,
                raw     : false,
                sameSite: Cookie::SAMESITE_LAX,
            )
        );
    }
}
