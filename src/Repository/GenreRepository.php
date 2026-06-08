<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Genre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Genre>
 */
class GenreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Genre::class);
    }

    /**
     * Retourne tous les genres actifs pour un type de projet donné,
     * triés par orderIndex puis slug.
     * Les genres avec projectTypes vide sont inclus (= tous types).
     *
     * @return Genre[]
     */
    public function findActiveForType(string $projectType): array
    {
        $all = $this->createQueryBuilder('g')
            ->leftJoin('g.translations', 't')
            ->addSelect('t')
            ->where('g.isActive = true')
            ->orderBy('g.orderIndex', 'ASC')
            ->addOrderBy('g.slug', 'ASC')
            ->getQuery()
            ->getResult();

        // Filtrage PHP : genre compatible avec le type OU universel
        return array_values(array_filter(
            $all,
            fn(Genre $g) => $g->supportsType($projectType)
        ));
    }

    /**
     * Retourne tous les genres (actifs + inactifs) pour l'admin,
     * avec translations pré-chargées.
     *
     * @return Genre[]
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.translations', 't')
            ->addSelect('t')
            ->orderBy('g.orderIndex', 'ASC')
            ->addOrderBy('g.slug', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne un genre par son slug avec ses traductions.
     */
    public function findBySlugWithTranslations(string $slug): ?Genre
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.translations', 't')
            ->addSelect('t')
            ->where('g.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne une map [slug => Genre] pour plusieurs slugs d'un coup.
     * Utilisé pour afficher les genres d'un projet sans N+1.
     *
     * @param  string[] $slugs
     * @return array<string, Genre>
     */
    public function findMapBySlugs(array $slugs): array
    {
        if (empty($slugs)) {
            return [];
        }

        $genres = $this->createQueryBuilder('g')
            ->leftJoin('g.translations', 't')
            ->addSelect('t')
            ->where('g.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($genres as $genre) {
            $map[$genre->slug] = $genre;
        }
        return $map;
    }
}
