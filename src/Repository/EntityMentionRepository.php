<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EntityMention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntityMention>
 */
class EntityMentionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntityMention::class);
    }

    /**
     * Supprime toutes les mentions d'une source donnée.
     * Appelé avant de re-parser un élément modifié.
     */
    public function deleteBySource(string $sourceType, int $sourceId): void
    {
        $this->createQueryBuilder('em')
            ->delete()
            ->where('em.sourceType = :sourceType')
            ->andWhere('em.sourceId = :sourceId')
            ->setParameter('sourceType', $sourceType)
            ->setParameter('sourceId', $sourceId)
            ->getQuery()
            ->execute();
    }

    /**
     * Trouve toutes les sources qui mentionnent une cible donnée.
     *
     * @return EntityMention[]
     */
    public function findByTarget(string $targetType, int $targetId): array
    {
        return $this->createQueryBuilder('em')
            ->where('em.targetType = :targetType')
            ->andWhere('em.targetId = :targetId')
            ->setParameter('targetType', $targetType)
            ->setParameter('targetId', $targetId)
            ->orderBy('em.sourceType', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les mentions d'un projet.
     *
     * @return EntityMention[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('em')
            ->join('em.project', 'p')
            ->where('p.id = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getResult();
    }
}
