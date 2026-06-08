<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Repository\ProjectMemberRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages de profil créateur dans l'interface workshop.
 *
 * - show()   : profil public d'un créateur + ses projets publics
 *              (si c'est son propre profil : tous ses projets + mode édition inline)
 * - update() : endpoint AJAX pour modifier son propre profil (bio, nom)
 */
final class CreatorController extends AbstractController
{
    public function __construct(
        private readonly UserRepository          $userRepository,
        private readonly ProjectRepository       $projectRepository,
        private readonly ProjectMemberRepository $projectMemberRepository,
        private readonly EntityManagerInterface  $em,
    ) {}

    // ══════════════════════════════════════════════════════════════════════
    // PROFIL CRÉATEUR
    // ══════════════════════════════════════════════════════════════════════

    public function show(string $username, Request $request): Response
    {
        $creator = $this->userRepository->findOneBy(['username' => $username]);

        if ($creator === null || $creator->isBanned()) {
            throw $this->createNotFoundException("Créateur introuvable : $username");
        }

        $currentUser  = $this->getUser();
        $isOwnProfile = $currentUser !== null && $currentUser->getUserIdentifier() === $creator->getUserIdentifier();

        // Projets visibles : tous (propre profil) ou seulement publics (autres)
        $projects = $isOwnProfile
            ? $this->projectRepository->findByUserWithStats($creator)
            : $this->projectRepository->findPublicByCreator($creator);

        // Map [projectId => role] pour le visiteur connecté :
        // permet aux cartes publiques d'afficher le bouton "Atelier"
        // si le visiteur est collaborateur sur un projet du créateur.
        $viewerRolesMap = [];
        if ($currentUser !== null && !$isOwnProfile) {
            $viewerRolesMap = $this->projectMemberRepository->findRoleMapByUser(
                $currentUser->getId()
            );
        }

        return $this->render('workshop/creator/show.html.twig', [
            'creator'           => $creator,
            'projects'          => $projects,
            'is_own_profile'    => $isOwnProfile,
            'public_count'      => count($this->projectRepository->findPublicByCreator($creator)),
            'viewer_roles_map'  => $viewerRolesMap,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // SOUTIEN (LIKE) — toggle
    // ══════════════════════════════════════════════════════════════════════

    public function toggleLike(string $username): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $creator = $this->userRepository->findOneBy(['username' => $username]);
        if ($creator === null || $creator->isBanned()) {
            throw $this->createNotFoundException("Créateur introuvable : $username");
        }

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        // Impossible de se soutenir soi-même
        if ($creator->getUserIdentifier() === $currentUser->getUserIdentifier()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous soutenir vous-même.'], 403);
        }

        if ($creator->isLikedBy($currentUser)) {
            $creator->removeLikedBy($currentUser);
            $liked = false;
        } else {
            $creator->addLikedBy($currentUser);
            $liked = true;
        }

        $this->em->flush();

        return $this->json([
            'liked' => $liked,
            'count' => $creator->getLikesCount(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // MISE À JOUR DU PROFIL (AJAX)
    // ══════════════════════════════════════════════════════════════════════

    public function update(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Vérification CSRF
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('update_profile', $token)) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Données invalides.'], Response::HTTP_BAD_REQUEST);
        }

        // On n'accepte que les champs autorisés
        if (isset($data['bio'])) {
            $user->setBio(mb_substr(trim((string) $data['bio']), 0, 500));
        }
        if (isset($data['firstName']) && trim($data['firstName']) !== '') {
            $user->setFirstName(mb_substr(trim((string) $data['firstName']), 0, 255));
        }
        if (isset($data['lastName']) && trim($data['lastName']) !== '') {
            $user->setLastName(mb_substr(trim((string) $data['lastName']), 0, 255));
        }

        $this->em->flush();

        return $this->json([
            'success'   => true,
            'fullName'  => $user->getFullName(),
            'bio'       => $user->getBio() ?? '',
        ]);
    }
}
