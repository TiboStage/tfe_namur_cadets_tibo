<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Retourne les dernières actions d'un projet (journal d'activité).
     *
     * @return ActivityLog[]
     */
    public function findRecentByProject(int $projectId, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les actions d'un utilisateur (toutes projets confondus).
     *
     * @return ActivityLog[]
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
