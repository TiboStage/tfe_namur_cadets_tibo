<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Retourne toutes les notes d'un projet, triées par priorité puis date.
     *
     * @return Note[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('n.priority', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les notes liées à une entité précise.
     *
     * @return Note[]
     */
    public function findByLinkedEntity(int $projectId, string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.project = :projectId')
            ->andWhere('n.linkedEntityType = :type')
            ->andWhere('n.linkedEntityId = :entityId')
            ->setParameter('projectId', $projectId)
            ->setParameter('type', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les todos non terminés assignés à un utilisateur.
     *
     * @return Note[]
     */
    public function findTodosByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.assignedTo = :userId')
            ->andWhere('n.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'todo')
            ->orderBy('n.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
