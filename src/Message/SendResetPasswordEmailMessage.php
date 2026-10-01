<?php

namespace App\Message;

/**
 * Dispatché plutôt que d'envoyer l'email directement, pour que le worker
 * Messenger fasse l'appel SMTP en arrière-plan (voir SendResetPasswordEmailMessageHandler).
 *
 * On transporte l'id utilisateur (pas l'entité) : le handler tourne dans un
 * process séparé, il doit recharger l'utilisateur via le repository plutôt
 * que réutiliser un objet Doctrine potentiellement détaché.
 */
final class SendResetPasswordEmailMessage
{
    public function __construct(
        public readonly int $userId,
        public readonly string $token,
        public readonly \DateTimeInterface $expiresAt,
    ) {
    }
}
