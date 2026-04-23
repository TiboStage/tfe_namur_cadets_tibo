<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Récupère les projets d'un utilisateur avec stats complètes.
     * Optimisation N+1 : 1 requête au lieu de 1 + (N × 3).
     *
     * @return array Tableau de résultats avec clés : 0 = Project, character_count, location_count, scene_count
     */
    public function findByUserWithStats(User $user): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(
                'p',
                'COUNT(DISTINCT c.id) AS character_count',
                'COUNT(DISTINCT l.id) AS location_count',
                'COUNT(DISTINCT s.id) AS scene_count'
            )
            ->leftJoin('p.characters', 'c')
            ->leftJoin('p.locations', 'l')
            ->leftJoin('p.scenarioElements', 's')
            ->where('p.createdBy = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id')
            ->orderBy('p.updatedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
