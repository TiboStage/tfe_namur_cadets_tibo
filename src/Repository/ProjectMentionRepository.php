<?php

namespace App\Repository;

use App\Entity\ProjectMention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectMentionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectMention::class);
    }

    /**
     * Retourne les mentions d'un projet triées par occurrences décroissantes.
     *
     * @return ProjectMention[]
     */
    public function findByProjectOrderedByCount(int $projectId): array
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('pm.occurrenceCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrouve ou crée une mention pour une entité précise.
     */
    public function findOneByEntity(int $projectId, string $entityType, int $entityId): ?ProjectMention
    {
        return $this->createQueryBuilder('pm')
            ->where('pm.project = :projectId')
            ->andWhere('pm.entityType = :type')
            ->andWhere('pm.entityId = :entityId')
            ->setParameter('projectId', $projectId)
            ->setParameter('type', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
