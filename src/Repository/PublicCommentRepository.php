<?php

namespace App\Repository;

use App\Entity\PublicComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PublicCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicComment::class);
    }

    /**
     * Retourne les commentaires approuvés d'un projet, du plus récent au plus ancien.
     *
     * @return PublicComment[]
     */
    public function findApprovedByProject(int $projectId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :projectId')
            ->andWhere('c.isApproved = true')
            ->andWhere('c.isSpam = false')
            ->setParameter('projectId', $projectId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
