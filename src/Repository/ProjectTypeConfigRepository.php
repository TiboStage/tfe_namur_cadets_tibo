<?php
// ─── INSTRUCTIONS ─────────────────────────────────────────────────────────────
// Ce fichier contient TOUS les repositories.
// Chaque classe doit être dans son propre fichier dans src/Repository/
// Noms de fichiers attendus listés ci-dessous.
// ─────────────────────────────────────────────────────────────────────────────

// ProjectTypeConfigRepository.php
namespace App\Repository;
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
}
