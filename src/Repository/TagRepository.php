<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Retourne tous les tags d'un projet, triés alphabétiquement.
     *
     * @return Tag[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
