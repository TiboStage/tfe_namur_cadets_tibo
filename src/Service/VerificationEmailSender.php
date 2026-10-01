<?php

namespace App\Service;

use App\Entity\User;
use App\Message\SendVerificationEmailMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Envoi (et renvoi) du mail de confirmation d'inscription, avec les mêmes
 * limites que le mot de passe oublié : 1 envoi toutes les 2 min, 3 par heure.
 *
 * Utilisé par RegistrationController (1er envoi) et EmailVerificationController (renvois).
 */
final class VerificationEmailSender
{
    public const int MAX_PER_HOUR = 3;
    public const int COOLDOWN_SECONDS = 120;

    public function __construct(
        private readonly MessageBusInterface $bus,
        #[Autowire(service: 'limiter.verify_email_cooldown')]
        private readonly RateLimiterFactoryInterface $cooldownLimiter,
        #[Autowire(service: 'limiter.verify_email')]
        private readonly RateLimiterFactoryInterface $hourlyLimiter,
    ) {
    }

    /**
     * Met le mail en file d'attente pour le worker.
     *
     * @return int Nombre d'envois encore possibles cette heure
     *
     * @throws RateLimitExceededException Trop tôt (getLimit() === 1) ou plafond horaire atteint
     */
    public function send(User $user): int
    {
        $key = mb_strtolower($user->getEmail());

        $this->cooldownLimiter->create($key)->consume()->ensureAccepted();
        $hourly = $this->hourlyLimiter->create($key)->consume()->ensureAccepted();

        $this->bus->dispatch(new SendVerificationEmailMessage($user->getId()));

        return $hourly->getRemainingTokens();
    }
}
