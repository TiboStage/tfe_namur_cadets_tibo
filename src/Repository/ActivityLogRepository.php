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

    /**
     * Retourne les actions d'un utilisateur sur un projet précis.
     *
     * @return ActivityLog[]
     */
    public function findByUserAndProject(int $userId, int $projectId, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :userId')
            ->andWhere('a.project = :projectId')
            ->setParameter('userId', $userId)
            ->setParameter('projectId', $projectId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les N dernières actions d'un utilisateur pour le dashboard.
     * Charge le projet en une seule requête (JOIN FETCH).
     *
     * @return ActivityLog[]
     */
    public function findRecentByUser(int $userId, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.project', 'p')
            ->addSelect('p')
            ->where('a.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
