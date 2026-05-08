<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EntityMention;
use App\Entity\Project;
use App\Entity\ScenarioElement;
use App\Repository\EntityMentionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service MentionParser
 *
 * Scanne le contenu JSON d'un ScenarioElement à la recherche de mentions
 * et met à jour la table entity_mention en conséquence.
 *
 * Format attendu dans le JSON (TipTap) :
 * {
 *   "type": "mention",
 *   "attrs": {
 *     "entityType": "character",  // ou "location"
 *     "entityId": 42
 *   }
 * }
 *
 * Usage depuis un Controller ou EventSubscriber :
 *   $mentionParser->parseAndSync($scenarioElement);
 */
final class MentionParser
{
    public function __construct(
        private readonly EntityManagerInterface   $em,
        private readonly EntityMentionRepository  $mentionRepository,
    ) {}

    /**
     * Parse le contenu JSON d'un ScenarioElement et synchronise les mentions.
     * Supprime les anciennes mentions de cet élément avant de recréer.
     */
    public function parseAndSync(ScenarioElement $element): void
    {
        $project = $element->getProject();

        // 1. Supprimer les anciennes mentions de cet élément
        $this->mentionRepository->deleteBySource('scenario_element', $element->getId());

        // 2. Parser le contenu JSON
        $mentions = $this->extractMentions($element->getContent());

        if (empty($mentions)) {
            return;
        }

        // 3. Créer les nouvelles mentions
        foreach ($mentions as ['entityType' => $targetType, 'entityId' => $targetId]) {
            $mention = new EntityMention();
            $mention->setProject($project)
                ->setSourceType('scenario_element')
                ->setSourceId($element->getId())
                ->setTargetType($targetType)
                ->setTargetId($targetId);

            $this->em->persist($mention);
        }

        $this->em->flush();
    }

    /**
     * Extrait toutes les mentions d'un contenu JSON TipTap.
     *
     * @param array $content Le JSONB du ScenarioElement
     * @return array<int, array{entityType: string, entityId: int}>
     */
    public function extractMentions(array $content): array
    {
        $mentions = [];
        $this->traverseNodes($content, $mentions);

        // Dédoublonnage : une même entité peut être citée plusieurs fois
        $unique = [];
        foreach ($mentions as $mention) {
            $key = $mention['entityType'] . '_' . $mention['entityId'];
            $unique[$key] = $mention;
        }

        return array_values($unique);
    }

    /**
     * Parcourt récursivement les noeuds TipTap pour trouver les mentions.
     */
    private function traverseNodes(array $nodes, array &$mentions): void
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            // Noeud de type "mention" TipTap
            if (
                isset($node['type']) &&
                $node['type'] === 'mention' &&
                isset($node['attrs']['entityType'], $node['attrs']['entityId'])
            ) {
                $entityType = $node['attrs']['entityType'];
                $entityId   = (int) $node['attrs']['entityId'];

                // Valider le type
                if (in_array($entityType, ['character', 'location'], strict: true) && $entityId > 0) {
                    $mentions[] = [
                        'entityType' => $entityType,
                        'entityId'   => $entityId,
                    ];
                }
            }

            // Descendre dans les enfants
            if (isset($node['content']) && is_array($node['content'])) {
                $this->traverseNodes($node['content'], $mentions);
            }
        }
    }
}
