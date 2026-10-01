<?php

namespace App\Message;

/**
 * Envoi du mail de confirmation d'inscription, traité par le worker Messenger
 * (voir SendVerificationEmailMessageHandler).
 *
 * Seul l'id est transporté : le lien signé est généré par le handler, au moment
 * de l'envoi, pour que sa durée de validité démarre quand le mail part vraiment.
 */
final class SendVerificationEmailMessage
{
    public function __construct(
        public readonly int $userId,
    ) {
    }
}
