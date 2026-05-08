<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    /**
     * Récupère tous les ScenarioElements et Notes où ce personnage est cité
     * via la table entity_mention.
     *
     * Retourne un tableau structuré :
     * [
     *   'scenario_elements' => ScenarioElement[],
     *   'notes'             => Note[],
     * ]
     */
    public function findMentions(Character $character): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Récupère les IDs des sources via une requête native (plus performant qu'un DQL polymorphique)
        $sql = <<<SQL
            SELECT source_type, source_id
            FROM entity_mention
            WHERE target_type = 'character'
              AND target_id   = :characterId
        SQL;

        $rows = $conn->executeQuery($sql, ['characterId' => $character->getId()])->fetchAllAssociative();

        // Groupe par source_type
        $scenarioElementIds = [];
        $noteIds = [];

        foreach ($rows as $row) {
            match ($row['source_type']) {
                'scenario_element' => $scenarioElementIds[] = (int) $row['source_id'],
                'note'             => $noteIds[]            = (int) $row['source_id'],
                default            => null,
            };
        }

        $em = $this->getEntityManager();

        return [
            'scenario_elements' => empty($scenarioElementIds) ? [] :
                $em->getRepository(\App\Entity\ScenarioElement::class)
                    ->findBy(['id' => $scenarioElementIds]),

            'notes' => empty($noteIds) ? [] :
                $em->getRepository(\App\Entity\Note::class)
                    ->findBy(['id' => $noteIds]),
        ];
    }

    /**
     * Récupère tous les personnages d'un projet.
     *
     * @return Character[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
