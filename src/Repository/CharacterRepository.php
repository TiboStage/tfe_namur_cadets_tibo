<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    /**
     * Retourne tous les personnages d'un projet, triés par nom.
     *
     * @return Character[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche full-text dans les noms et biographies des personnages.
     *
     * @return Character[]
     */
    public function search(int $projectId, string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :projectId')
            ->andWhere(
                'c.name LIKE :q OR c.firstName LIKE :q OR c.lastName LIKE :q
                 OR c.nickname LIKE :q OR c.biography LIKE :q'
            )
            ->setParameter('projectId', $projectId)
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les personnages par rôle narratif (protagonist, antagonist...).
     *
     * @return Character[]
     */
    public function findByProjectAndRole(int $projectId, string $role): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :projectId')
            ->andWhere('c.role = :role')
            ->setParameter('projectId', $projectId)
            ->setParameter('role', $role)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
