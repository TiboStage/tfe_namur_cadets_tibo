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

    // ─── Méthodes admin / modération ─────────────────────────────────────────

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPublic(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.visibility = :vis')
            ->setParameter('vis', Project::VISIBILITY_PUBLIC)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFlagged(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.reportCount > 0 OR p.moderationStatus != :clear')
            ->setParameter('clear', 'clear')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Projets signalés ou avec statut de modération non-neutre.
     *
     * @return Project[]
     */
    public function findFlagged(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.reportCount > 0 OR p.moderationStatus != :clear')
            ->setParameter('clear', 'clear')
            ->orderBy('p.reportCount', 'DESC')
            ->addOrderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les projets pour la liste admin, avec le owner en JOIN.
     *
     * @return Project[]
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.createdBy', 'u')
            ->addSelect('u')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ─── Atlas / Exploration publique ────────────────────────────────────────

    /**
     * Projets publics non-bloqués pour l'Atlas, avec filtres optionnels.
     *
     * @return Project[]
     */
    /**
     * Requête en 2 passes pour éviter le problème LIMIT + JOIN de Doctrine :
     *
     *  Passe 1 : SELECT p.id avec LIMIT/OFFSET + filtres → liste d'IDs paginée
     *  Passe 2 : SELECT p + JOIN relations (createdBy, scenarioElements, characters)
     *            WHERE p.id IN (:ids) → hydratation complète sans N+1
     *
     * Sans ce pattern, le LIMIT s'applique sur les lignes du JOIN (pas les projets)
     * et les collections scenarioElements/characters déclenchent 2×N requêtes lazy.
     */
    public function findPublicForExploration(
        ?string $type   = null,
        ?string $search = null,
        int     $limit  = 5,
        int     $offset = 0,
        ?string $genre  = null,
        string  $sort   = 'recent',
    ): array {
        // ── Passe 1 : récupérer les IDs paginés ───────────────────────────────
        $idQb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.visibility = :vis')
            ->andWhere('p.moderationStatus != :blocked')
            ->setParameter('vis', Project::VISIBILITY_PUBLIC)
            ->setParameter('blocked', 'blocked')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        match ($sort) {
            'oldest' => $idQb->orderBy('p.updatedAt', 'ASC'),
            'az'     => $idQb->orderBy('p.title',     'ASC'),
            'za'     => $idQb->orderBy('p.title',     'DESC'),
            default  => $idQb->orderBy('p.updatedAt', 'DESC'),
        };

        if ($type !== null && $type !== '') {
            $idQb->andWhere('p.projectType = :type')
                 ->setParameter('type', $type);
        }

        if ($search !== null && $search !== '') {
            $idQb->andWhere('LOWER(p.title) LIKE :search OR LOWER(p.description) LIKE :search')
                 ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($genre !== null && $genre !== '') {
            $idQb->join('p.projectFeatures', 'pf_genre')
                 ->andWhere('pf_genre.featureKey = :fk AND pf_genre.value = :genre')
                 ->setParameter('fk', 'genre')
                 ->setParameter('genre', $genre);
        }

        $ids = array_column($idQb->getQuery()->getScalarResult(), 'id');

        if (empty($ids)) {
            return [];
        }

        // ── Passe 2 : hydratation complète avec toutes les relations ──────────
        // Un seul SELECT avec JOIN → zéro requête N+1 dans le template
        $results = $this->createQueryBuilder('p')
            ->leftJoin('p.createdBy',        'u')  ->addSelect('u')
            ->leftJoin('p.scenarioElements', 'se') ->addSelect('se')
            ->leftJoin('p.characters',       'ch') ->addSelect('ch')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        // Réordonner selon l'ordre original des IDs (le IN() ne garantit pas l'ordre)
        $indexed = [];
        foreach ($results as $p) {
            $indexed[$p->getId()] = $p;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($indexed[$id])) {
                $ordered[] = $indexed[$id];
            }
        }

        return $ordered;
    }

    /**
     * Comptage pour la pagination — mêmes filtres que findPublicForExploration.
     */
    public function countPublicForExploration(
        ?string $type   = null,
        ?string $search = null,
        ?string $genre  = null,
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.visibility = :vis')
            ->andWhere('p.moderationStatus != :blocked')
            ->setParameter('vis', Project::VISIBILITY_PUBLIC)
            ->setParameter('blocked', 'blocked');

        if ($type !== null && $type !== '') {
            $qb->andWhere('p.projectType = :type')
               ->setParameter('type', $type);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('LOWER(p.title) LIKE :search OR LOWER(p.description) LIKE :search')
               ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($genre !== null && $genre !== '') {
            $qb->join('p.projectFeatures', 'pf_genre')
               ->andWhere('pf_genre.featureKey = :fk AND pf_genre.value = :genre')
               ->setParameter('fk', 'genre')
               ->setParameter('genre', $genre);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Retourne les slugs de genres pour une liste de projets.
     * Évite le N+1 — un seul appel pour tous les projets.
     *
     * @param  int[] $projectIds
     * @return array<int, string[]>  [ projectId => ['thriller', 'polar', …] ]
     */
    public function findGenreSlugsByProjectIds(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('IDENTITY(pf.project) AS pid, pf.value AS slug')
            ->join('p.projectFeatures', 'pf')
            ->where('pf.featureKey = :fk')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('fk', 'genre')
            ->setParameter('ids', $projectIds)
            ->getQuery()
            ->getScalarResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['pid']][] = $row['slug'];
        }
        return $map;
    }

    /**
     * Top créateurs par nombre de projets publics.
     * Retourne des tableaux ['id', 'username', 'firstName', 'lastName', 'project_count'].
     *
     * NOTE : on sélectionne des scalaires (pas l'entité u directement) car Doctrine
     * exige que l'alias racine (p) soit présent si on sélectionne une entité jointe.
     */
    public function findTopCreators(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->select('u.id', 'u.username', 'u.firstName', 'u.lastName', 'COUNT(p.id) AS project_count')
            ->innerJoin('p.createdBy', 'u')
            ->where('p.visibility = :vis')
            ->andWhere('p.moderationStatus != :blocked')
            ->setParameter('vis', Project::VISIBILITY_PUBLIC)
            ->setParameter('blocked', 'blocked')
            ->groupBy('u.id')
            ->orderBy('project_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Projets publics d'un créateur donné (pour sa page de profil publique).
     *
     * @return Project[]
     */
    public function findPublicByCreator(User $creator): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdBy = :creator')
            ->andWhere('p.visibility = :vis')
            ->andWhere('p.moderationStatus != :blocked')
            ->setParameter('creator', $creator)
            ->setParameter('vis', Project::VISIBILITY_PUBLIC)
            ->setParameter('blocked', 'blocked')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ─── Helpers viewer / explore ────────────────────────────────────────────

    /**
     * Retourne tous les IDs de projets dont l'utilisateur est propriétaire.
     * Utilisé pour construire la viewer_roles_map sur les pages publiques.
     *
     * @return int[]
     */
    public function findOwnedProjectIds(int $userId): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    // ─── Méthodes utilisateur ─────────────────────────────────────────────────

    /**
     * Batch-load 4 collections pour éviter le N+1 sur la liste de projets.
     * Réduit N×4 requêtes lazy à 4 requêtes IN indépendantes du nombre de projets.
     *
     * @param Project[] $projects
     */
    public function preloadCollections(array $projects): void
    {
        if (empty($projects)) {
            return;
        }

        $ids = array_map(fn(Project $p) => $p->getId(), $projects);
        $em  = $this->getEntityManager();

        foreach (['tasks', 'scenarioElements', 'characters', 'locations'] as $assoc) {
            $em->createQuery("SELECT p, a FROM App\\Entity\\Project p LEFT JOIN p.$assoc a WHERE p.id IN (:ids)")
                ->setParameter('ids', $ids)
                ->getResult();
        }
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
