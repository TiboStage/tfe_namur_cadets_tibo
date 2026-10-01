<?php

namespace App\MessageHandler;

use App\Message\SendVerificationEmailMessage;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Construit le lien signé de confirmation et envoie le mail —
 * exécuté par `messenger:consume async`, en dehors de la requête d'inscription.
 */
#[AsMessageHandler]
final class SendVerificationEmailMessageHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendVerificationEmailMessage $message): void
    {
        $user = $this->userRepository->find($message->userId);
        if ($user === null || $user->isVerified()) {
            // Compte supprimé ou déjà confirmé entre-temps : rien à envoyer.
            return;
        }

        // Lien signé (id + email + expiration) : impossible à falsifier,
        // et invalide dès que l'email du compte change.
        $signature = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId(), '_locale' => $user->locale],
        );

        $fromEmail = $_ENV['MAILER_FROM'] ?? 'system@scenart.be';

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'Scénart'))
            ->to($user->getEmail())
            ->subject('Confirmez votre adresse email — Scénart')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'signedUrl'             => $signature->getSignedUrl(),
                'expiresAtMessageKey'   => $signature->getExpirationMessageKey(),
                'expiresAtMessageData'  => $signature->getExpirationMessageData(),
                'user'                  => $user,
            ]);

        $this->mailer->send($email);
    }
}
