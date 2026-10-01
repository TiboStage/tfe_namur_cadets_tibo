<?php

namespace App\MessageHandler;

use App\Message\SendResetPasswordEmailMessage;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

/**
 * Construit et envoie réellement l'email de réinitialisation — exécuté par
 * `messenger:consume async`, en dehors de la requête HTTP qui l'a déclenché.
 */
#[AsMessageHandler]
final class SendResetPasswordEmailMessageHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendResetPasswordEmailMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if ($user === null) {
            // Compte supprimé entre la demande et le traitement du message : rien à envoyer.
            return;
        }

        $fromEmail = $_ENV['MAILER_FROM'] ?? 'system@scenart.be';

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'Scénart'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe — Scénart')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'resetTokenValue' => $message->token,
                'expiresAt'       => $message->expiresAt,
                'user'            => $user,
            ]);

        $this->mailer->send($email);
    }
}
