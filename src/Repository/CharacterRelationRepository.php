<?php

namespace App\Repository;

use App\Entity\CharacterRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CharacterRelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CharacterRelation::class);
    }

    /**
     * Retourne toutes les relations d'un personnage (en tant que A ou B).
     *
     * @return CharacterRelation[]
     */
    public function findAllForCharacter(int $characterId): array
    {
        return $this->createQueryBuilder('cr')
            ->where('cr.characterA = :id OR cr.characterB = :id')
            ->setParameter('id', $characterId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une relation existe déjà entre deux personnages.
     */
    public function relationExists(int $characterAId, int $characterBId): bool
    {
        $count = $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->where(
                '(cr.characterA = :a AND cr.characterB = :b)
                 OR (cr.characterA = :b AND cr.characterB = :a)'
            )
            ->setParameter('a', $characterAId)
            ->setParameter('b', $characterBId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Retourne toutes les relations d'un projet entier.
     * Utile pour construire le graphe de relations.
     *
     * @return CharacterRelation[]
     */
    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('cr')
            ->join('cr.characterA', 'a')
            ->join('cr.characterB', 'b')
            ->where('a.project = :projectId')
            ->setParameter('projectId', $projectId)
            ->getQuery()
            ->getResult();
    }
}
