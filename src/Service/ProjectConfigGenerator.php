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
    // GÉNÉRATION PAR PROFONDEUR (NOUVEAU FUNNEL)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Génère les configs narratives selon le type + le nombre de niveaux choisi.
     *
     * @param array $levelNames Noms personnalisés [0 => 'Acte', 1 => 'Scène', …]
     *                          Les entrées vides tombent sur les valeurs du preset.
     */
    public function generateConfigsForDepth(
        Project $project,
        string  $projectType,
        int     $depth,
        array   $levelNames = [],
    ): void {
        if (!in_array($depth, [2, 3, 4], true)) {
            $depth = 3;
        }

        $presets = $this->getStructurePresets();
        $type    = in_array($projectType, ['film', 'serie', 'jeu_video'], true)
            ? $projectType
            : 'film'; // fallback sécuritaire

        $levels = $presets[$type][$depth];

        foreach ($levels as $i => $preset) {
            // Nom personnalisé s'il est fourni et non vide
            $customSingular = isset($levelNames[$i]) && trim($levelNames[$i]) !== ''
                ? trim($levelNames[$i])
                : null;

            $config = new ProjectTypeConfig();
            $config->setProject($project);
            $config->projectType             = $projectType;
            $config->depth                   = $i + 1;
            $config->elementType             = $preset['elementType'];
            $config->labelSingular           = $customSingular ?? $preset['labelSingular'];
            $config->labelPlural             = $customSingular !== null
                ? $customSingular . 's'
                : $preset['labelPlural'];
            $config->hasContent              = $preset['hasContent'];
            $config->hasDuration             = $preset['hasDuration'];
            $config->defaultDurationSeconds  = $preset['defaultDurationSeconds'];
            $config->icon                    = $preset['icon'];
            $config->color                   = $preset['color'];

            $this->em->persist($config);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // GÉNÉRATION DES CONFIGS PAR DÉFAUT (rétro-compat)
    // ═══════════════════════════════════════════════════════════════

    public function generateDefaultConfigs(Project $project, string $projectType): void
    {
        $this->generateConfigsForDepth($project, $projectType, 3);
    }

    // ═══════════════════════════════════════════════════════════════
    // GÉNÉRATION DES CONFIGS CUSTOM (rétro-compat)
    // ═══════════════════════════════════════════════════════════════

    /** @deprecated Utiliser generateConfigsForDepth() */
    public function generateCustomConfigs(Project $project, array $customStructure): void
    {
        foreach ($customStructure as $level) {
            $config = new ProjectTypeConfig();
            $config->setProject($project);
            $config->projectType            = $project->getProjectType();
            $config->depth                  = $level['depth'];
            $config->elementType            = 'custom_' . $level['depth'];
            $config->labelSingular          = $level['label'];
            $config->labelPlural            = $level['label'] . 's';
            $config->hasContent             = $level['hasContent'] ?? false;
            $config->hasDuration            = true;
            $config->defaultDurationSeconds = $this->getDefaultDuration($project->getProjectType());
            $config->icon                   = $this->getDefaultIcon($level['depth']);
            $config->color                  = $this->getDefaultColor($level['depth']);

            $this->em->persist($config);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // TABLE DE PRESETS — type × profondeur
    // ═══════════════════════════════════════════════════════════════

    /**
     * Retourne la table complète : type → depth → array de niveaux.
     * Chaque niveau : labelSingular, labelPlural, elementType, hasContent,
     *                 hasDuration, defaultDurationSeconds, icon, color.
     */
    private function getStructurePresets(): array
    {
        return [
            'film' => [
                2 => [
                    ['labelSingular' => 'Acte',      'labelPlural' => 'Actes',      'elementType' => 'act',      'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📖', 'color' => '#E63946'],
                    ['labelSingular' => 'Scène',     'labelPlural' => 'Scènes',     'elementType' => 'scene',    'hasContent' => true,  'hasDuration' => true,  'defaultDurationSeconds' => 300, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
                3 => [
                    ['labelSingular' => 'Acte',      'labelPlural' => 'Actes',      'elementType' => 'act',      'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📖', 'color' => '#E63946'],
                    ['labelSingular' => 'Séquence',  'labelPlural' => 'Séquences',  'elementType' => 'sequence', 'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '🎬', 'color' => '#F4A261'],
                    ['labelSingular' => 'Scène',     'labelPlural' => 'Scènes',     'elementType' => 'scene',    'hasContent' => true,  'hasDuration' => true,  'defaultDurationSeconds' => 300, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
                4 => [
                    ['labelSingular' => 'Acte',      'labelPlural' => 'Actes',      'elementType' => 'act',      'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📖', 'color' => '#E63946'],
                    ['labelSingular' => 'Séquence',  'labelPlural' => 'Séquences',  'elementType' => 'sequence', 'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '🎬', 'color' => '#F4A261'],
                    ['labelSingular' => 'Scène',     'labelPlural' => 'Scènes',     'elementType' => 'scene',    'hasContent' => false, 'hasDuration' => true,  'defaultDurationSeconds' => 300, 'icon' => '🎭', 'color' => '#457B9D'],
                    ['labelSingular' => 'Sous-scène','labelPlural' => 'Sous-scènes','elementType' => 'subscene', 'hasContent' => true,  'hasDuration' => true,  'defaultDurationSeconds' => 60,  'icon' => '✨', 'color' => '#2A9D8F'],
                ],
            ],
            'serie' => [
                2 => [
                    ['labelSingular' => 'Saison',  'labelPlural' => 'Saisons',  'elementType' => 'season',  'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📺', 'color' => '#E63946'],
                    ['labelSingular' => 'Épisode', 'labelPlural' => 'Épisodes', 'elementType' => 'episode', 'hasContent' => true,  'hasDuration' => true,  'defaultDurationSeconds' => 0,   'icon' => '🎞️', 'color' => '#2A9D8F'],
                ],
                3 => [
                    ['labelSingular' => 'Saison',  'labelPlural' => 'Saisons',  'elementType' => 'season',  'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📺', 'color' => '#E63946'],
                    ['labelSingular' => 'Épisode', 'labelPlural' => 'Épisodes', 'elementType' => 'episode', 'hasContent' => false, 'hasDuration' => true,  'defaultDurationSeconds' => 0,   'icon' => '🎞️', 'color' => '#F4A261'],
                    ['labelSingular' => 'Scène',   'labelPlural' => 'Scènes',   'elementType' => 'scene',   'hasContent' => true,  'hasDuration' => true,  'defaultDurationSeconds' => 240, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
                4 => [
                    ['labelSingular' => 'Saison',  'labelPlural' => 'Saisons',  'elementType' => 'season',  'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📺', 'color' => '#E63946'],
                    ['labelSingular' => 'Épisode', 'labelPlural' => 'Épisodes', 'elementType' => 'episode', 'hasContent' => false, 'hasDuration' => true,  'defaultDurationSeconds' => 0,   'icon' => '🎞️', 'color' => '#F4A261'],
                    ['labelSingular' => 'Acte',    'labelPlural' => 'Actes',    'elementType' => 'act',     'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0,   'icon' => '📖', 'color' => '#457B9D'],
                    ['labelSingular' => 'Scène',   'labelPlural' => 'Scènes',   'elementType' => 'scene',   'hasContent' => true,  'hasDuration' => true,  'defaultDurationSeconds' => 240, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
            ],
            'jeu_video' => [
                2 => [
                    ['labelSingular' => 'Chapitre', 'labelPlural' => 'Chapitres', 'elementType' => 'chapter', 'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🎮', 'color' => '#E63946'],
                    ['labelSingular' => 'Scène',    'labelPlural' => 'Scènes',    'elementType' => 'scene',   'hasContent' => true,  'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
                3 => [
                    ['labelSingular' => 'Chapitre', 'labelPlural' => 'Chapitres', 'elementType' => 'chapter', 'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🎮', 'color' => '#E63946'],
                    ['labelSingular' => 'Niveau',   'labelPlural' => 'Niveaux',   'elementType' => 'level',   'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🗺️', 'color' => '#F4A261'],
                    ['labelSingular' => 'Scène',    'labelPlural' => 'Scènes',    'elementType' => 'scene',   'hasContent' => true,  'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
                4 => [
                    ['labelSingular' => 'Chapitre', 'labelPlural' => 'Chapitres', 'elementType' => 'chapter', 'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🎮', 'color' => '#E63946'],
                    ['labelSingular' => 'Niveau',   'labelPlural' => 'Niveaux',   'elementType' => 'level',   'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🗺️', 'color' => '#F4A261'],
                    ['labelSingular' => 'Zone',     'labelPlural' => 'Zones',     'elementType' => 'zone',    'hasContent' => false, 'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '📍', 'color' => '#457B9D'],
                    ['labelSingular' => 'Scène',    'labelPlural' => 'Scènes',    'elementType' => 'scene',   'hasContent' => true,  'hasDuration' => false, 'defaultDurationSeconds' => 0, 'icon' => '🎭', 'color' => '#2A9D8F'],
                ],
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
