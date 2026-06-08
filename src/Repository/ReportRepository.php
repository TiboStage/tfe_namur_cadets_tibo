<?php

declare(strict_types=1);

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

    /** @return Report[] */
    public function findAllForModo(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.reporter', 'u')
            ->addSelect('u')
            ->leftJoin('r.targetProject', 'tp')
            ->addSelect('tp')
            ->leftJoin('r.targetComment', 'tc')
            ->addSelect('tc')
            ->leftJoin('r.targetUser', 'tu')
            ->addSelect('tu')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();
    }

    /** @return Report[] */
    public function findPending(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.reporter', 'u')
            ->addSelect('u')
            ->leftJoin('r.targetProject', 'tp')
            ->addSelect('tp')
            ->leftJoin('r.targetComment', 'tc')
            ->addSelect('tc')
            ->leftJoin('r.targetUser', 'tu')
            ->addSelect('tu')
            ->where('r.status = :status')
            ->setParameter('status', Report::STATUS_PENDING)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', Report::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasAlreadyReported(int $reporterId, string $targetType, int $targetId): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.reporter = :reporter')
            ->andWhere('r.targetType = :type')
            ->setParameter('reporter', $reporterId)
            ->setParameter('type', $targetType);

        match ($targetType) {
            Report::TYPE_PROJECT => $qb->andWhere('r.targetProject = :t')->setParameter('t', $targetId),
            Report::TYPE_COMMENT => $qb->andWhere('r.targetComment = :t')->setParameter('t', $targetId),
            Report::TYPE_USER    => $qb->andWhere('r.targetUser = :t')->setParameter('t', $targetId),
        };

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
