<?php

namespace App\Repository;

use App\Entity\ProjectMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectMember::class);
    }

    /**
     * Vérifie si un utilisateur est membre d'un projet.
     */
    public function isMember(int $projectId, int $userId): bool
    {
        return null !== $this->createQueryBuilder('pm')
            ->where('pm.project = :projectId')
            ->andWhere('pm.user = :userId')
            ->setParameter('projectId', $projectId)
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne tous les membres d'un projet avec leur rôle.
     *
     * @return ProjectMember[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('pm')
            ->join('pm.user', 'u')
            ->addSelect('u')
            ->where('pm.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('pm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
