<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CommentVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentVote::class);
    }

    /**
     * Retourne les données de vote pour tous les commentaires d'un projet.
     *
     * Résultat : [commentId => ['up' => int, 'down' => int, 'user_vote' => 'up'|'down'|null]]
     *
     * @return array<int, array{up: int, down: int, user_vote: string|null}>
     */
    public function findVoteDataByProject(int $projectId, ?int $userId): array
    {
        $rows = $this->createQueryBuilder('cv')
            ->select('IDENTITY(cv.comment) AS comment_id', 'cv.value', 'IDENTITY(cv.user) AS user_id')
            ->join('cv.comment', 'c')
            ->where('c.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $cid = (int) $row['comment_id'];
            if (!isset($result[$cid])) {
                $result[$cid] = ['up' => 0, 'down' => 0, 'user_vote' => null];
            }
            if ($row['value'] === 'up') {
                $result[$cid]['up']++;
            } else {
                $result[$cid]['down']++;
            }
            if ($userId !== null && (int) $row['user_id'] === $userId) {
                $result[$cid]['user_vote'] = $row['value'];
            }
        }

        return $result;
    }

    public function findByCommentAndUser(int $commentId, int $userId): ?CommentVote
    {
        return $this->findOneBy(['comment' => $commentId, 'user' => $userId]);
    }
}
