<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Entity\User;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\ScenarioElementRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page publique d'un projet — accessible sans authentification.
 *
 * Accessible si le projet est 'public'.
 * Si l'utilisateur connecté est propriétaire ou collaborateur,
 * un bouton "Ouvrir dans l'atelier" est affiché.
 */
final class PublicProjectController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository       $commentRepository,
        private readonly CommentVoteRepository   $commentVoteRepository,
        private readonly ScenarioElementRepository $scenarioElementRepository,
    ) {}

    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        // Seuls les projets "En ligne" sont visibles publiquement
        if ($project->getVisibility() !== Project::VISIBILITY_PUBLIC
            || $project->getModerationStatus() === 'blocked'
        ) {
            throw $this->createNotFoundException('Ce projet n\'est pas disponible publiquement.');
        }

        /** @var User|null $viewer */
        $viewer      = $this->getUser();
        $isOwner     = false;
        $isMember    = false;
        $canWorkshop = false;

        if ($viewer !== null) {
            $isOwner = $project->getCreatedBy()?->getId() === $viewer->getId();

            if (!$isOwner) {
                foreach ($project->getProjectMembers() as $member) {
                    if ($member->getUser()?->getId() === $viewer->getId()) {
                        $isMember = true;
                        break;
                    }
                }
            }

            $canWorkshop = $isOwner || $isMember;
        }

        $comments  = $this->commentRepository->findByProject($project->getId());
        $voteData  = $this->commentVoteRepository->findVoteDataByProject(
            $project->getId(),
            $viewer?->getId()
        );

        return $this->render('workshop/project-public/show.html.twig', [
            'project'       => $project,
            'is_owner'      => $isOwner,
            'is_member'     => $isMember,
            'can_workshop'  => $canWorkshop,
            'can_reply'     => $canWorkshop,   // seuls les owner/collabs peuvent répondre
            'comments'      => $comments,
            'comment_count' => $this->commentRepository->countByProject($project->getId()),
            'viewer'        => $viewer,
            'vote_data'     => $voteData,
        ]);
    }

    // ── Lecteur public d'un élément narratif ─────────────────────────────────

    public function reader(
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project,
        int $id
    ): Response {
        if ($project->getVisibility() !== Project::VISIBILITY_PUBLIC
            || $project->getModerationStatus() === 'blocked'
        ) {
            throw $this->createNotFoundException('Ce projet n\'est pas disponible publiquement.');
        }

        $element = $this->scenarioElementRepository->find($id);

        if ($element === null || $element->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException('Élément introuvable.');
        }

        return $this->render('workshop/project-public/reader.html.twig', [
            'project' => $project,
            'element' => $element,
            'viewer'  => $this->getUser(),
        ]);
    }
}
