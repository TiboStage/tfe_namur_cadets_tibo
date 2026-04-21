<?php

namespace App\Repository;

use App\Entity\FeatureDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeatureDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureDefinition::class);
    }

    /**
     * Retourne les définitions de features pour un type de projet,
     * triées par ordre d'affichage.
     *
     * @return FeatureDefinition[]
     */
    public function findByProjectType(string $projectType): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.projectType = :type')
            ->setParameter('type', $projectType)
            ->orderBy('f.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
