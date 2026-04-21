<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ScenarioElement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ScenarioElementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScenarioElement::class);
    }

    /**
     * Éléments racine (depth=1, sans parent).
     * @return ScenarioElement[]
     */
    public function findRootElements(Project $project): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.project = :project')
            ->andWhere('e.parent IS NULL')
            ->setParameter('project', $project)
            ->orderBy('e.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Éléments racine avec tous les enfants chargés (pour la sidebar).
     * @return ScenarioElement[]
     */
    public function findRootElementsWithChildren(Project $project): array
    {
        // Charge tout le projet en une requête, Doctrine gère l'hydratation
        $all = $this->createQueryBuilder('e')
            ->where('e.project = :project')
            ->setParameter('project', $project)
            ->orderBy('e.depth', 'ASC')
            ->addOrderBy('e.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();

        // Retourne uniquement les racines — les enfants sont accessibles via $element->getChildren()
        return array_filter($all, fn($e) => $e->getParent() === null);
    }

    /**
     * Frères et sœurs d'un élément (même parent).
     * @return ScenarioElement[]
     */
    public function findSiblings(ScenarioElement $element): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.project = :project')
            ->setParameter('project', $element->getProject())
            ->orderBy('e.orderIndex', 'ASC');

        if ($element->getParent()) {
            $qb->andWhere('e.parent = :parent')
                ->setParameter('parent', $element->getParent());
        } else {
            $qb->andWhere('e.parent IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les enfants pour calculer le prochain orderIndex.
     */
    public function countByParent(?ScenarioElement $parent, Project $project): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.project = :project')
            ->setParameter('project', $project);

        if ($parent) {
            $qb->andWhere('e.parent = :parent')->setParameter('parent', $parent);
        } else {
            $qb->andWhere('e.parent IS NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
