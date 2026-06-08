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
     * Projets où l'utilisateur est collaborateur (pas propriétaire).
     * Si $role est fourni, filtre par rôle.
     *
     * @return ProjectMember[]
     */
    public function findByUser(int $userId, ?string $role = null): array
    {
        $qb = $this->createQueryBuilder('pm')
            ->join('pm.project', 'p')
            ->addSelect('p')
            ->where('pm.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.updatedAt', 'DESC');

        if ($role !== null) {
            $qb->andWhere('pm.role = :role')->setParameter('role', $role);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de projets en collaboration pour un utilisateur.
     */
    public function countByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('pm')
            ->select('COUNT(pm.user)')
            ->where('pm.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne une map [projectId => role] pour toutes les collaborations d'un utilisateur.
     * Utile pour déterminer le rôle du visiteur sur chaque carte publique.
     *
     * @return array<int, string>
     */
    public function findRoleMapByUser(int $userId): array
    {
        $rows = $this->createQueryBuilder('pm')
            ->select('IDENTITY(pm.project) AS project_id', 'pm.role')
            ->where('pm.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['project_id']] = $row['role'];
        }

        return $map;
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
