<?php

declare(strict_types=1);

namespace App\Controller\Workshop;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Notification;
use App\Entity\Project;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gestion des commentaires sur la page publique d'un projet.
 * Accessible uniquement aux utilisateurs connectés.
 */
#[IsGranted('ROLE_USER')]
class CommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly CommentRepository       $commentRepository,
        private readonly CommentVoteRepository   $commentVoteRepository,
        private readonly TranslatorInterface     $translator,
    ) {}

    // ── Poster un commentaire ─────────────────────────────────────────────────

    public function new(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Project $project
    ): Response {
        if ($project->getVisibility() !== Project::VISIBILITY_PUBLIC) {
            throw $this->createNotFoundException('Ce projet n\'est pas accessible publiquement.');
        }

        if (!$this->isCsrfTokenValid('comment_new_' . $project->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('csrf.invalid', [], 'flash_messages'));
            return $this->redirectToRoute('app_project_public', [
                '_locale' => $request->getLocale(),
                'slug'    => $project->getSlug(),
            ]);
        }

        $content = trim($request->request->getString('content'));

        if (strlen($content) < 2 || strlen($content) > 2000) {
            $this->addFlash('error', $this->translator->trans('comment.length_invalid', [], 'flash_messages'));
            return $this->redirectToRoute('app_project_public', [
                '_locale' => $request->getLocale(),
                'slug'    => $project->getSlug(),
            ]);
        }

        $comment = new Comment();
        $comment->content = $content;
        $comment->setAuthor($this->getUser());
        $comment->setProject($project);

        // Réponse à un commentaire parent (profondeur 1 max)
        $parentId = $request->request->getInt('parent_id');
        if ($parentId > 0) {
            $parent = $this->commentRepository->find($parentId);
            if ($parent !== null
                && $parent->getProject()->getId() === $project->getId()
                && $parent->getParent() === null   // on n'imbrique pas plus d'un niveau
            ) {
                $comment->setParent($parent);
            }
        }

        $this->em->persist($comment);

        // ── Notifications ───────────────────────────────────────────────────────
        $currentUser = $this->getUser();
        $commentUrl  = $this->generateUrl('app_project_public', [
            '_locale'   => $request->getLocale(),
            'slug'      => $project->getSlug(),
        ]) . '#comment-' . $comment->getId();

        if ($comment->getParent() !== null) {
            // C'est une réponse : notifier l'auteur du commentaire parent
            $parentAuthor = $comment->getParent()->getAuthor();
            if ($parentAuthor !== null && $parentAuthor->getId() !== $currentUser?->getId()) {
                $this->createNotification(
                    $parentAuthor,
                    'reply',
                    sprintf('%s a répondu à votre commentaire sur « %s »', $currentUser?->getUsername(), $project->getTitle()),
                    $commentUrl
                );
            }
        } else {
            // Commentaire racine : notifier le propriétaire et les co-responsables
            $recipients = [];

            $owner = $project->getCreatedBy();
            if ($owner !== null && $owner->getId() !== $currentUser?->getId()) {
                $recipients[$owner->getId()] = $owner;
            }

            foreach ($project->getProjectMembers() as $member) {
                if ($member->getRole() === 'lead') {
                    $memberUser = $member->getUser();
                    if ($memberUser !== null
                        && $memberUser->getId() !== $currentUser?->getId()
                        && !isset($recipients[$memberUser->getId()])
                    ) {
                        $recipients[$memberUser->getId()] = $memberUser;
                    }
                }
            }

            foreach ($recipients as $recipient) {
                $this->createNotification(
                    $recipient,
                    'comment',
                    sprintf('%s a commenté le projet « %s »', $currentUser?->getUsername(), $project->getTitle()),
                    $commentUrl
                );
            }
        }

        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('comment.published', [], 'flash_messages'));

        return $this->redirectToRoute('app_project_public', [
            '_locale'   => $request->getLocale(),
            'slug'      => $project->getSlug(),
            '_fragment' => 'comments',
        ]);
    }

    // ── Voter sur un commentaire (👍 / 👎) ───────────────────────────────────

    public function vote(Request $request, int $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if ($comment === null) {
            return $this->json(['error' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $token = (string) ($data['_token'] ?? '');
        $value = in_array($data['value'] ?? '', ['up', 'down'], true) ? $data['value'] : 'up';

        if (!$this->isCsrfTokenValid('comment_vote_' . $id, $token)) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        /** @var \App\Entity\User $user */
        $user       = $this->getUser();
        $existing   = $this->commentVoteRepository->findByCommentAndUser($id, $user->getId());

        if ($existing !== null) {
            if ($existing->value === $value) {
                // Même vote → on l'annule (toggle off)
                $this->em->remove($existing);
                $userVote = null;
            } else {
                // Vote différent → on change
                $existing->value = $value;
                $userVote = $value;
            }
        } else {
            // Nouveau vote
            $vote = new CommentVote();
            $vote->setComment($comment);
            $vote->setUser($user);
            $vote->value = $value;
            $this->em->persist($vote);
            $userVote = $value;
        }

        $this->em->flush();

        // Recompute counts
        $voteData = $this->commentVoteRepository->findVoteDataByProject(
            $comment->getProject()->getId(),
            $user->getId()
        );
        $cv = $voteData[$comment->getId()] ?? ['up' => 0, 'down' => 0, 'user_vote' => null];

        return $this->json([
            'upvotes'   => $cv['up'],
            'downvotes' => $cv['down'],
            'user_vote' => $cv['user_vote'],
        ]);
    }

    // ── Supprimer un commentaire ──────────────────────────────────────────────

    public function delete(Request $request, int $id): Response
    {
        $comment = $this->commentRepository->find($id);

        if ($comment === null) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        if (!$this->isCsrfTokenValid('comment_delete_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('error', $this->translator->trans('csrf.invalid', [], 'flash_messages'));
            return $this->redirectToRoute('app_project_public', [
                '_locale' => $request->getLocale(),
                'slug'    => $comment->getProject()->getSlug(),
            ]);
        }

        $user     = $this->getUser();
        $isAuthor = $comment->getAuthor()?->getId() === $user?->getId();
        $isModo   = $this->isGranted('ROLE_MODO');

        if (!$isAuthor && !$isModo) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce commentaire.');
        }

        $slug = $comment->getProject()->getSlug();

        $this->em->remove($comment);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('comment.deleted', [], 'flash_messages'));

        return $this->redirectToRoute('app_project_public', [
            '_locale'   => $request->getLocale(),
            'slug'      => $slug,
            '_fragment' => 'comments',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createNotification(
        \App\Entity\User $recipient,
        string           $type,
        string           $content,
        ?string          $link = null
    ): void {
        $notif = new Notification();
        $notif->setUser($recipient);
        $notif->type    = $type;
        $notif->content = $content;
        $notif->setLink($link);
        $this->em->persist($notif);
    }
}
