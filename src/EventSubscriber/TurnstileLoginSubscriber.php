<?php

namespace App\EventSubscriber;

use App\Service\TurnstileService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Vérifie le token Turnstile sur la connexion avant que Symfony Security
 * ne valide l'identifiant et le mot de passe.
 *
 * CheckPassportEvent est dispatché par tous les authenticators Symfony —
 * on filtre en vérifiant que la requête courante est bien un POST vers login.
 */
class TurnstileLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TurnstileService $turnstile,
        private readonly RequestStack     $requestStack,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Priorité 512 → s'exécute avant la vérification du mot de passe
        return [CheckPassportEvent::class => ['onCheckPassport', 512]];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        // On ne vérifie que les POST vers la route de login
        // (évite d'interférer avec d'autres authenticators éventuels)
        if (!str_contains($request->getPathInfo(), '/login')) {
            return;
        }

        $token = $request->request->get('cf-turnstile-response', '');

        if (!$this->turnstile->verify($token, $request->getClientIp())) {
            throw new CustomUserMessageAuthenticationException('captcha.invalid');
        }
    }
}
