<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\WorldEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorldEvent>
 */
class WorldEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorldEvent::class);
    }

    /**
     * Récupère tous les événements d'un projet triés chronologiquement.
     *
     * @return WorldEvent[]
     */
    public function findByProjectChronological(Project $project): array
    {
        return $this->createQueryBuilder('we')
            ->leftJoin('we.location', 'l')
            ->addSelect('l')
            ->where('we.project = :project')
            ->setParameter('project', $project)
            ->orderBy('we.year', 'ASC')
            ->addOrderBy('we.month', 'ASC')
            ->addOrderBy('we.day', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements liés à un lieu donné.
     *
     * @return WorldEvent[]
     */
    public function findByLocation(int $locationId): array
    {
        return $this->createQueryBuilder('we')
            ->join('we.location', 'l')
            ->where('l.id = :locationId')
            ->setParameter('locationId', $locationId)
            ->orderBy('we.year', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
