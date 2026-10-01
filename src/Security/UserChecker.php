<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie l'état du compte à chaque connexion (formulaire ET cookie "se souvenir de moi").
 *
 * Les contrôles sont faits APRÈS la vérification du mot de passe (checkPostAuth) :
 * sans le bon mot de passe, on ne révèle jamais qu'un compte est banni ou non confirmé,
 * ce qui trahirait son existence.
 *
 * Les clés de message sont traduites dans le domaine 'security' (security.fr.yaml).
 */
class UserChecker implements UserCheckerInterface
{
    /** Clé de l'erreur "email non confirmé" — testée dans auth.html.twig et SecurityController. */
    public const string UNVERIFIED = 'account.unverified';

    public const string BANNED = 'account.banned';

    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBanned()) {
            throw new CustomUserMessageAccountStatusException(self::BANNED);
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(self::UNVERIFIED);
        }
    }
}
