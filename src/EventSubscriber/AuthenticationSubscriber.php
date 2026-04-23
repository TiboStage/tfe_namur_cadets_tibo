<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        $firstName = method_exists($user, 'getFirstName')
            ? $user->getFirstName()
            : 'Utilisateur';

        $request = $this->requestStack->getCurrentRequest();
        if ($request && $session = $request->getSession()) {
            $message = $this->translator->trans('flash.auth.welcome', [
                '{firstName}' => $firstName
            ], 'flash_messages');  // ← DOMAINE AJOUTÉ

            $session->getFlashBag()->add('success', $message);
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        if ($session = $request->getSession()) {
            $message = $this->translator->trans('flash.auth.logout', [], 'flash_messages');  // ← DOMAINE AJOUTÉ
            $session->getFlashBag()->add('info', $message);
        }
    }
}
