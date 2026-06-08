<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Création des tables genre et genre_translation.
 *
 * genre            → liste des genres narratifs gérés par l'admin
 * genre_translation → traductions FR/NL/EN de chaque genre
 *
 * Le slug est la clé stable stockée dans project_feature.value
 * (feature_key = 'genre').
 */
final class Version20260601100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables genre et genre_translation';
    }

    public function up(Schema $schema): void
    {
        // ── Table genre ───────────────────────────────────────────────────────
        $this->addSql("
            CREATE TABLE genre (
                id            SERIAL PRIMARY KEY,
                slug          VARCHAR(50)  NOT NULL UNIQUE,
                project_types JSON         NOT NULL DEFAULT '[]',
                is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
                order_index   INT          NOT NULL DEFAULT 0
            )
        ");

        // ── Table genre_translation ───────────────────────────────────────────
        $this->addSql("
            CREATE TABLE genre_translation (
                id       SERIAL PRIMARY KEY,
                genre_id INT          NOT NULL REFERENCES genre(id) ON DELETE CASCADE,
                locale   VARCHAR(5)   NOT NULL,
                label    VARCHAR(100) NOT NULL,
                CONSTRAINT uniq_genre_locale UNIQUE (genre_id, locale)
            )
        ");

        // ── Index utiles ─────────────────────────────────────────────────────
        $this->addSql("CREATE INDEX idx_genre_active     ON genre (is_active, order_index)");
        $this->addSql("CREATE INDEX idx_genre_trans_locale ON genre_translation (locale)");

        // ── Données initiales — genres les plus courants ──────────────────────
        // Insérés ici pour que la plateforme soit directement utilisable.
        // L'admin peut les modifier/supprimer via l'interface.
        $genres = [
            //  slug                | projectTypes         | order
            ['thriller',            '[]',                  0 ],
            ['polar',               '["film","serie"]',    1 ],
            ['drame',               '[]',                  2 ],
            ['comedie',             '[]',                  3 ],
            ['action_aventure',     '[]',                  4 ],
            ['horreur',             '[]',                  5 ],
            ['science_fiction',     '[]',                  6 ],
            ['fantasy',             '[]',                  7 ],
            ['romance',             '["film","serie"]',    8 ],
            ['historique',          '["film","serie"]',    9 ],
            ['biopic',              '["film","serie"]',    10],
            ['animation',           '["film","serie"]',    11],
            ['documentaire',        '["film"]',            12],
            ['action_rpg',          '["jeu_video"]',       13],
            ['rpg',                 '["jeu_video"]',       14],
            ['aventure',            '["jeu_video"]',       15],
            ['visual_novel',        '["jeu_video"]',       16],
            ['strategie',           '["jeu_video"]',       17],
        ];

        $translations = [
            'thriller'         => ['fr' => 'Thriller',         'nl' => 'Thriller',          'en' => 'Thriller'         ],
            'polar'            => ['fr' => 'Polar',            'nl' => 'Politieroman',       'en' => 'Crime / Noir'     ],
            'drame'            => ['fr' => 'Drame',            'nl' => 'Drama',              'en' => 'Drama'            ],
            'comedie'          => ['fr' => 'Comédie',          'nl' => 'Komedie',            'en' => 'Comedy'           ],
            'action_aventure'  => ['fr' => 'Action / Aventure','nl' => 'Actie / Avontuur',   'en' => 'Action / Adventure'],
            'horreur'          => ['fr' => 'Horreur',          'nl' => 'Horror',             'en' => 'Horror'           ],
            'science_fiction'  => ['fr' => 'Science-Fiction',  'nl' => 'Sciencefictie',      'en' => 'Science Fiction'  ],
            'fantasy'          => ['fr' => 'Fantasy',          'nl' => 'Fantasy',            'en' => 'Fantasy'          ],
            'romance'          => ['fr' => 'Romance',          'nl' => 'Romantiek',          'en' => 'Romance'          ],
            'historique'       => ['fr' => 'Historique',       'nl' => 'Historisch',         'en' => 'Historical'       ],
            'biopic'           => ['fr' => 'Biopic',           'nl' => 'Biografie',          'en' => 'Biopic'           ],
            'animation'        => ['fr' => 'Animation',        'nl' => 'Animatie',           'en' => 'Animation'        ],
            'documentaire'     => ['fr' => 'Documentaire',     'nl' => 'Documentaire',       'en' => 'Documentary'      ],
            'action_rpg'       => ['fr' => 'Action-RPG',       'nl' => 'Actie-RPG',          'en' => 'Action RPG'       ],
            'rpg'              => ['fr' => 'RPG',               'nl' => 'RPG',                'en' => 'RPG'              ],
            'aventure'         => ['fr' => 'Aventure',          'nl' => 'Avontuur',           'en' => 'Adventure'        ],
            'visual_novel'     => ['fr' => 'Visual Novel',      'nl' => 'Visual Novel',       'en' => 'Visual Novel'     ],
            'strategie'        => ['fr' => 'Stratégie',         'nl' => 'Strategie',          'en' => 'Strategy'         ],
        ];

        foreach ($genres as [$slug, $types, $order]) {
            $this->addSql(
                "INSERT INTO genre (slug, project_types, is_active, order_index) VALUES (?, ?::json, TRUE, ?)",
                [$slug, $types, $order]
            );
        }

        foreach ($translations as $slug => $locales) {
            foreach ($locales as $locale => $label) {
                $this->addSql(
                    "INSERT INTO genre_translation (genre_id, locale, label)
                     SELECT id, ?, ? FROM genre WHERE slug = ?",
                    [$locale, $label, $slug]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS genre_translation");
        $this->addSql("DROP TABLE IF EXISTS genre");
    }
}
