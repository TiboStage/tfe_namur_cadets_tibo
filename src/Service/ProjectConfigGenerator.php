<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\ProjectTypeConfig;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de génération des configurations de structure narrative.
 *
 * Génère les ProjectTypeConfig selon le type de projet (Film/Série/JV)
 * ou depuis une structure personnalisée (mode Custom).
 */
class ProjectConfigGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // GÉNÉRATION DES CONFIGS PAR DÉFAUT (MODE SIMPLIFIÉ)
    // ═══════════════════════════════════════════════════════════════

    public function generateDefaultConfigs(Project $project, string $projectType): void
    {
        $configs = match ($projectType) {
            'film'      => $this->getFilmConfigs(),
            'serie'     => $this->getSerieConfigs(),
            'jeu_video' => $this->getJeuVideoConfigs(),
            default     => throw new \InvalidArgumentException("Type de projet invalide : $projectType"),
        };

        foreach ($configs as $configData) {
            $config = new ProjectTypeConfig();
            $config->setProject($project);
            $config->projectType = $projectType;
            $config->depth = $configData['depth'];
            $config->elementType = $configData['elementType'];
            $config->labelSingular = $configData['labelSingular'];
            $config->labelPlural = $configData['labelPlural'];
            $config->hasContent = $configData['hasContent'];
            $config->hasDuration = $configData['hasDuration'];
            $config->defaultDurationSeconds = $configData['defaultDurationSeconds'];
            $config->icon = $configData['icon'];
            $config->color = $configData['color'];

            $this->em->persist($config);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // GÉNÉRATION DES CONFIGS CUSTOM (MODE CUSTOM)
    // ═══════════════════════════════════════════════════════════════

    public function generateCustomConfigs(Project $project, array $customStructure): void
    {
        foreach ($customStructure as $index => $level) {
            $config = new ProjectTypeConfig();
            $config->setProject($project);
            $config->projectType = $project->getProjectType();
            $config->depth = $level['depth'];
            $config->elementType = 'custom_' . $level['depth'];
            $config->labelSingular = $level['label'];
            $config->labelPlural = $level['label'] . 's'; // Simple pluralisation
            $config->hasContent = $level['hasContent'] ?? false;
            $config->hasDuration = true;
            $config->defaultDurationSeconds = $this->getDefaultDuration($project->getProjectType());
            $config->icon = $this->getDefaultIcon($level['depth']);
            $config->color = $this->getDefaultColor($level['depth']);

            $this->em->persist($config);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIGS PAR DÉFAUT - FILM
    // ═══════════════════════════════════════════════════════════════

    private function getFilmConfigs(): array
    {
        return [
            [
                'depth'                  => 1,
                'elementType'            => 'act',
                'labelSingular'          => 'Acte',
                'labelPlural'            => 'Actes',
                'hasContent'             => false,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 0,
                'icon'                   => '📖',
                'color'                  => '#E63946',
            ],
            [
                'depth'                  => 2,
                'elementType'            => 'sequence',
                'labelSingular'          => 'Séquence',
                'labelPlural'            => 'Séquences',
                'hasContent'             => false,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 0,
                'icon'                   => '🎬',
                'color'                  => '#457B9D',
            ],
            [
                'depth'                  => 3,
                'elementType'            => 'scene',
                'labelSingular'          => 'Scène',
                'labelPlural'            => 'Scènes',
                'hasContent'             => true,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 300, // 5 minutes
                'icon'                   => '🎭',
                'color'                  => '#2A9D8F',
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIGS PAR DÉFAUT - SÉRIE
    // ═══════════════════════════════════════════════════════════════

    private function getSerieConfigs(): array
    {
        return [
            [
                'depth'                  => 1,
                'elementType'            => 'season',
                'labelSingular'          => 'Saison',
                'labelPlural'            => 'Saisons',
                'hasContent'             => false,
                'hasDuration'            => false,
                'defaultDurationSeconds' => 0,
                'icon'                   => '📺',
                'color'                  => '#E63946',
            ],
            [
                'depth'                  => 2,
                'elementType'            => 'episode',
                'labelSingular'          => 'Épisode',
                'labelPlural'            => 'Épisodes',
                'hasContent'             => false,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 0,
                'icon'                   => '🎞️',
                'color'                  => '#F4A261',
            ],
            [
                'depth'                  => 3,
                'elementType'            => 'act',
                'labelSingular'          => 'Acte',
                'labelPlural'            => 'Actes',
                'hasContent'             => false,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 0,
                'icon'                   => '📖',
                'color'                  => '#457B9D',
            ],
            [
                'depth'                  => 4,
                'elementType'            => 'scene',
                'labelSingular'          => 'Scène',
                'labelPlural'            => 'Scènes',
                'hasContent'             => true,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 240, // 4 minutes
                'icon'                   => '🎭',
                'color'                  => '#2A9D8F',
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIGS PAR DÉFAUT - JEU VIDÉO
    // ═══════════════════════════════════════════════════════════════

    private function getJeuVideoConfigs(): array
    {
        return [
            [
                'depth'                  => 1,
                'elementType'            => 'chapter',
                'labelSingular'          => 'Chapitre',
                'labelPlural'            => 'Chapitres',
                'hasContent'             => false,
                'hasDuration'            => false,
                'defaultDurationSeconds' => 0,
                'icon'                   => '🎮',
                'color'                  => '#E63946',
            ],
            [
                'depth'                  => 2,
                'elementType'            => 'quest_type',
                'labelSingular'          => 'Type de Quête',
                'labelPlural'            => 'Types de Quêtes',
                'hasContent'             => false,
                'hasDuration'            => false,
                'defaultDurationSeconds' => 0,
                'icon'                   => '🗺️',
                'color'                  => '#F4A261',
            ],
            [
                'depth'                  => 3,
                'elementType'            => 'poi',
                'labelSingular'          => 'Point d\'Intérêt',
                'labelPlural'            => 'Points d\'Intérêt',
                'hasContent'             => false,
                'hasDuration'            => false,
                'defaultDurationSeconds' => 0,
                'icon'                   => '📍',
                'color'                  => '#457B9D',
            ],
            [
                'depth'                  => 4,
                'elementType'            => 'scene',
                'labelSingular'          => 'Scène',
                'labelPlural'            => 'Scènes',
                'hasContent'             => true,
                'hasDuration'            => true,
                'defaultDurationSeconds' => 0, // Variable gameplay
                'icon'                   => '🎭',
                'color'                  => '#2A9D8F',
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function getDefaultDuration(string $projectType): int
    {
        return match ($projectType) {
            'film'      => 300, // 5 minutes
            'serie'     => 240, // 4 minutes
            'jeu_video' => 0,   // Variable
            default     => 300,
        };
    }

    private function getDefaultIcon(int $depth): string
    {
        return match ($depth) {
            1 => '📁',
            2 => '📂',
            3 => '📄',
            4 => '🎭',
            5 => '✨',
            default => '📌',
        };
    }

    private function getDefaultColor(int $depth): string
    {
        return match ($depth) {
            1 => '#E63946',
            2 => '#F4A261',
            3 => '#457B9D',
            4 => '#2A9D8F',
            5 => '#6B7280',
            default => '#888888',
        };
    }
}
