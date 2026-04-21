<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Retourne les tâches d'un projet groupées par statut.
     * (Utile pour un affichage kanban)
     *
     * @return Task[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les tâches assignées à un utilisateur dans un projet.
     *
     * @return Task[]
     */
    public function findByProjectAndAssignee(int $projectId, int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.project = :projectId')
            ->andWhere('t.assignedTo = :userId')
            ->setParameter('projectId', $projectId)
            ->setParameter('userId', $userId)
            ->orderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
