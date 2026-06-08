<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vérifie la disponibilité d'un nom d'utilisateur en temps réel.
 *
 * GET /api/check-username?username=xxx[&exclude=userId]
 * → { "available": true|false|null }
 *
 * `exclude` permet d'ignorer l'utilisateur courant dans la vérification
 * (utile dans le formulaire de profil : le pseudo actuel est "disponible" pour soi-même).
 */
class CheckUsernameController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function check(Request $request): JsonResponse
    {
        $username  = strtolower(trim($request->query->getString('username')));
        $excludeId = $request->query->get('exclude');

        // Trop court → pas de réponse (pas d'erreur non plus)
        if (mb_strlen($username) < 3) {
            return $this->json(['available' => null]);
        }

        $existing = $this->userRepository->findOneBy(['username' => $username]);

        if ($existing === null) {
            return $this->json(['available' => true]);
        }

        // Cas profil : l'utilisateur consulte son propre pseudo
        if ($excludeId !== null && (string) $existing->getId() === (string) $excludeId) {
            return $this->json(['available' => true]);
        }

        return $this->json(['available' => false]);
    }
}
