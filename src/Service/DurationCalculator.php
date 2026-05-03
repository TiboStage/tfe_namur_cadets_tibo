<?php

namespace App\Service;

/**
 * Service de calcul automatique de la durée des scènes.
 *
 * Règle : 1 mot de dialogue = 0.5 seconde
 * Seuls les blocs de type "dialogue" sont comptés.
 */
class DurationCalculator
{
    private const SECONDS_PER_WORD = 0.5;

    /**
     * Calcule la durée automatique basée sur les dialogues uniquement.
     *
     * @param array $content Contenu JSONB du ScenarioElement
     *                       [
     *                         {"type": "slug", "content": "INT. APPARTEMENT - JOUR"},
     *                         {"type": "dialogue", "content": "Bonjour tout le monde"}
     *                       ]
     *
     * @return int Durée en secondes
     */
    public function calculateAutoDuration(array $content): int
    {
        $dialogueWords = 0;

        foreach ($content as $block) {
            if (($block['type'] ?? '') === 'dialogue') {
                $text = strip_tags($block['content'] ?? '');
                $dialogueWords += str_word_count($text);
            }
        }

        return (int)($dialogueWords * self::SECONDS_PER_WORD);
    }

    /**
     * Formate une durée en secondes en format lisible.
     *
     * @param int $seconds
     * @return string Ex: "2min 30s" ou "1h 15min"
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds === 0) {
            return '0s';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}min";
        }
        if ($secs > 0 || empty($parts)) {
            $parts[] = "{$secs}s";
        }

        return implode(' ', $parts);
    }

    /**
     * Convertit une durée en minutes/secondes vers secondes totales.
     *
     * @param int $minutes
     * @param int $seconds
     * @return int Total en secondes
     */
    public function convertToSeconds(int $minutes, int $seconds = 0): int
    {
        return ($minutes * 60) + $seconds;
    }
}
