<?php
// ─── INSTRUCTIONS ─────────────────────────────────────────────────────────────
// Ce fichier contient TOUS les repositories.
// Chaque classe doit être dans son propre fichier dans src/Repository/
// Noms de fichiers attendus listés ci-dessous.
// ─────────────────────────────────────────────────────────────────────────────

// ProjectTypeConfigRepository.php
namespace App\Repository;
use App\Entity\Project;
use App\Entity\ProjectTypeConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ProjectTypeConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectTypeConfig::class);
    }

    /** Retourne toutes les configs pour un type de projet donné, triées par depth. */
    public function findByProjectType(string $projectType): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.projectType = :type')
            ->setParameter('type', $projectType)
            ->orderBy('c.depth', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Configs d'un projet spécifique, triées par depth.
     *
     * @return ProjectTypeConfig[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.depth', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un tableau indexé par depth : [1 => config, 2 => config, …]
     *
     * @return array<int, ProjectTypeConfig>
     */
    public function findMapByProject(Project $project): array
    {
        $map = [];
        foreach ($this->findByProject($project) as $config) {
            $map[$config->depth] = $config;
        }
        return $map;
    }
}
