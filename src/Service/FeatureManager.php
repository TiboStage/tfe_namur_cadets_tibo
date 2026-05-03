<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\ProjectFeature;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des features du projet.
 *
 * Les features sont activées en mode Custom uniquement.
 * En mode Simplifié, elles sont implicites selon le projectType.
 */
class FeatureManager
{
    private const VALID_FEATURES = [
        'tension_meter',       // Film
        'heartbeat',           // Film
        'storyline_tracker',   // Série
        'cliffhanger',         // Série
        'interactivity_label', // Jeu Vidéo
        'reality_conditioner', // Jeu Vidéo
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Active les features sélectionnées pour un projet (mode Custom).
     *
     * @param Project $project
     * @param array $featureKeys ['tension_meter', 'storyline_tracker', ...]
     */
    public function activateFeatures(Project $project, array $featureKeys): void
    {
        foreach ($featureKeys as $featureKey) {
            if (!in_array($featureKey, self::VALID_FEATURES)) {
                continue; // Skip invalid features
            }

            $feature = new ProjectFeature();
            $feature->setProject($project);
            $feature->featureKey = $featureKey;
            $feature->value = ''; // Pas de valeur initiale

            $this->em->persist($feature);
        }
    }

    /**
     * Vérifie si une feature est activée pour un projet.
     *
     * En mode Simplifié, on vérifie le projectType.
     * En mode Custom, on vérifie la table project_feature.
     */
    public function isFeatureActive(Project $project, string $featureKey): bool
    {
        // Mode Simplifié : features implicites
        if (!$project->settings['custom_mode'] ?? false) {
            return $this->isFeatureImplicitForType($project->getProjectType(), $featureKey);
        }

        // Mode Custom : vérification dans project_feature
        foreach ($project->getProjectFeatures() as $feature) {
            if ($feature->getFeatureKey() === $featureKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Features implicites selon le type de projet (mode Simplifié).
     */
    private function isFeatureImplicitForType(string $projectType, string $featureKey): bool
    {
        $implicitFeatures = [
            'film' => ['tension_meter', 'heartbeat'],
            'serie' => ['storyline_tracker', 'cliffhanger'],
            'jeu_video' => ['interactivity_label', 'reality_conditioner'],
        ];

        return in_array($featureKey, $implicitFeatures[$projectType] ?? []);
    }

    /**
     * Retourne toutes les features disponibles avec leurs métadonnées.
     */
    public function getAllFeatures(): array
    {
        return [
            'film' => [
                [
                    'key'         => 'tension_meter',
                    'label'       => 'Le Thermomètre de Tension',
                    'description' => 'Curseur d\'intensité dramatique (1-10) pour visualiser la courbe narrative.',
                ],
                [
                    'key'         => 'heartbeat',
                    'label'       => 'Le Battement de Cœur',
                    'description' => 'Type et rythme de chaque scène (Action, Dialogue, Silence, Exposition).',
                ],
            ],
            'serie' => [
                [
                    'key'         => 'storyline_tracker',
                    'label'       => 'Le Tracker d\'Intrigues',
                    'description' => 'Suivez vos storylines et détectez les intrigues oubliées.',
                ],
                [
                    'key'         => 'cliffhanger',
                    'label'       => 'Le Cliffhanger',
                    'description' => 'Intensité du suspense en fin d\'épisode (1-10).',
                ],
            ],
            'jeu_video' => [
                [
                    'key'         => 'interactivity_label',
                    'label'       => 'L\'Étiquette d\'Interactivité',
                    'description' => 'Type de contenu : Cinématique, Dialogue à choix, ou Phase de Gameplay.',
                ],
                [
                    'key'         => 'reality_conditioner',
                    'label'       => 'Le Conditionneur de Réalité',
                    'description' => 'Pré-requis narratifs pour débloquer des scènes (flags, items, états).',
                ],
            ],
        ];
    }
}
