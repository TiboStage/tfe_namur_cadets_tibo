<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Seed des genres narratifs (tables genre / genre_translation).
 *
 * Ces données étaient à l'origine insérées par la migration
 * Version20260601100000. Comme `doctrine:fixtures:load` purge les tables
 * sans rejouer les migrations, on les recrée ici pour qu'un reload complet
 * les conserve.
 *
 * Réinsérer SANS purger les autres données :
 *     php bin/console doctrine:fixtures:load --append --group=genres
 *
 * La garde (count > 0 → skip) évite les doublons en mode --append.
 */
final class GenreFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['genres'];
    }

    public function load(ObjectManager $manager): void
    {
        $conn = $manager->getConnection();

        // Garde : si des genres existent déjà, on ne fait rien (évite les doublons).
        $existing = (int) $conn->fetchOne('SELECT COUNT(*) FROM genre');
        if ($existing > 0) {
            return;
        }

        // slug => [projectTypes(json), order, [fr, nl, en]]
        $genres = [
            'thriller'        => ['[]',                  0,  ['Thriller',          'Thriller',        'Thriller']],
            'polar'           => ['[]',                  1,  ['Polar',             'Politieroman',    'Crime / Noir']],
            'drame'           => ['[]',                  2,  ['Drame',             'Drama',           'Drama']],
            'comedie'         => ['[]',                  3,  ['Comédie',           'Komedie',         'Comedy']],
            'action_aventure' => ['[]',                  4,  ['Action / Aventure', 'Actie / Avontuur','Action / Adventure']],
            'horreur'         => ['[]',                  5,  ['Horreur',           'Horror',          'Horror']],
            'science_fiction' => ['[]',                  6,  ['Science-Fiction',   'Sciencefictie',   'Science Fiction']],
            'fantasy'         => ['[]',                  7,  ['Fantasy',           'Fantasy',         'Fantasy']],
            'romance'         => ['[]',                  8,  ['Romance',           'Romantiek',       'Romance']],
            'historique'      => ['[]',                  9,  ['Historique',        'Historisch',      'Historical']],
            'biopic'          => ['[]',                  10, ['Biopic',            'Biografie',       'Biopic']],
            'animation'       => ['["film","serie"]',    11, ['Animation',         'Animatie',        'Animation']],
            'documentaire'    => ['["film","serie"]',    12, ['Documentaire',      'Documentaire',    'Documentary']],
            'action_rpg'      => ['["jeu_video"]',       13, ['Action-RPG',        'Actie-RPG',       'Action RPG']],
            'rpg'             => ['["jeu_video"]',       14, ['RPG',               'RPG',             'RPG']],
            'aventure'        => ['["jeu_video"]',       15, ['Aventure',          'Avontuur',        'Adventure']],
            'visual_novel'    => ['["jeu_video"]',       16, ['Visual Novel',      'Visual Novel',    'Visual Novel']],
            'strategie'       => ['["jeu_video"]',       17, ['Stratégie',         'Strategie',       'Strategy']],
        ];

        foreach ($genres as $slug => [$types, $order, $labels]) {
            $conn->executeStatement(
                'INSERT INTO genre (slug, project_types, is_active, order_index) VALUES (?, ?::json, TRUE, ?)',
                [$slug, $types, $order]
            );

            foreach (['fr', 'nl', 'en'] as $i => $locale) {
                $conn->executeStatement(
                    'INSERT INTO genre_translation (genre_id, locale, label)
                     SELECT id, ?, ? FROM genre WHERE slug = ?',
                    [$locale, $labels[$i], $slug]
                );
            }
        }
    }
}
