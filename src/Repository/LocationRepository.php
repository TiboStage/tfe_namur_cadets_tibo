<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * Retourne tous les lieux d'un projet, triés par nom.
     *
     * @return Location[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche full-text dans les noms et descriptions des lieux.
     *
     * @return Location[]
     */
    public function search(int $projectId, string $query): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.project = :projectId')
            ->andWhere('l.name LIKE :q OR l.description LIKE :q')
            ->setParameter('projectId', $projectId)
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
