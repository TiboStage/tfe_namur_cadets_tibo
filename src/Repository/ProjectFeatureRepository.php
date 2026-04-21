<?php

namespace App\Repository;

use App\Entity\ProjectFeature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectFeatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectFeature::class);
    }

    /**
     * Retourne la valeur d'une feature précise pour un projet.
     */
    public function findOneByProjectAndKey(int $projectId, string $featureKey): ?ProjectFeature
    {
        return $this->createQueryBuilder('pf')
            ->where('pf.project = :projectId')
            ->andWhere('pf.featureKey = :key')
            ->setParameter('projectId', $projectId)
            ->setParameter('key', $featureKey)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
