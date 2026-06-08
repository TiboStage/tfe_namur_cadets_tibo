<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Commentaires racines d'un projet (avec auteur + réponses eager-loaded).
     *
     * @return Comment[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.author', 'a')
            ->addSelect('a')
            ->leftJoin('c.replies', 'r')
            ->addSelect('r')
            ->leftJoin('r.author', 'ra')
            ->addSelect('ra')
            ->where('c.project = :projectId')
            ->andWhere('c.parent IS NULL')
            ->setParameter('projectId', $projectId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les commentaires visibles d'un projet.
     */
    public function countByProject(int $projectId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.project = :projectId')
            ->andWhere('c.status = :status')
            ->setParameter('projectId', $projectId)
            ->setParameter('status', 'visible')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Les N commentaires racines les plus récents d'un projet (pour le dashboard).
     *
     * @return Comment[]
     */
    public function findRecentByProject(int $projectId, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.author', 'a')
            ->addSelect('a')
            ->where('c.project = :projectId')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.status = :status')
            ->setParameter('projectId', $projectId)
            ->setParameter('status', 'visible')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les commentaires visibles pour une liste de projets en une seule requête.
     * Retourne un tableau [projectId => count].
     *
     * @param  int[] $projectIds
     * @return array<int, int>
     */
    public function countByProjectIds(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.project) AS pid, COUNT(c.id) AS cnt')
            ->where('c.project IN (:ids)')
            ->andWhere('c.status = :status')
            ->setParameter('ids',    $projectIds)
            ->setParameter('status', 'visible')
            ->groupBy('c.project')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['pid']] = (int) $row['cnt'];
        }
        return $map;
    }

    /**
     * Tous les commentaires pour le panel modo (les 100 derniers).
     *
     * @return Comment[]
     */
    public function findAllForModo(): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.author', 'a')
            ->addSelect('a')
            ->join('c.project', 'p')
            ->addSelect('p')
            ->leftJoin('c.parent', 'parent')
            ->addSelect('parent')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte total pour le dashboard modo.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
