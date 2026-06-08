<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Documentation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Documentation>
 */
class DocumentationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Documentation::class);
    }

    /**
     * Articles publiés, triés par catégorie puis orderIndex.
     * Les traductions sont chargées en même temps (EAGER via entité + LEFT JOIN).
     *
     * @return Documentation[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.translations', 't')
            ->addSelect('t')
            ->where('d.isPublished = true')
            ->orderBy('d.category', 'ASC')
            ->addOrderBy('d.orderIndex', 'ASC')
            ->addOrderBy('d.slug', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Navigation groupée : ['Catégorie' => [Documentation, ...], ...].
     * Les entités Documentation ont leurs traductions chargées.
     *
     * @return array<string, Documentation[]>
     */
    public function findPublishedGroupedByCategory(): array
    {
        $articles = $this->findPublished();

        $grouped = [];
        foreach ($articles as $article) {
            $grouped[$article->category][] = $article;
        }

        return $grouped;
    }

    /**
     * Charge un article avec toutes ses traductions (pour l'admin).
     */
    public function findWithTranslations(int $id): ?Documentation
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.translations', 't')
            ->addSelect('t')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Tous les articles pour l'admin, triés par catégorie + orderIndex.
     *
     * @return Documentation[]
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.translations', 't')
            ->addSelect('t')
            ->orderBy('d.category', 'ASC')
            ->addOrderBy('d.orderIndex', 'ASC')
            ->addOrderBy('d.slug', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
