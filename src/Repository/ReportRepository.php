<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * Retourne les signalements en attente, triés du plus récent au plus ancien.
     *
     * @return Report[]
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les signalements d'un projet.
     *
     * @return Report[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
