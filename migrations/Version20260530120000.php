<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Système de traduction pour la documentation (fr/nl/en).
 *
 * 1. Crée la table documentation_translation
 * 2. Migre les données existantes (title + content) comme traductions FR
 * 3. Supprime title et content de la table documentation
 */
final class Version20260530120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Documentation multilingue : crée documentation_translation et migre les données FR existantes.';
    }

    public function up(Schema $schema): void
    {
        // 1. Crée la table de traductions
        $this->addSql(<<<'SQL'
            CREATE TABLE documentation_translation (
                id           SERIAL PRIMARY KEY,
                documentation_id INT NOT NULL,
                locale       VARCHAR(5)   NOT NULL DEFAULT 'fr',
                title        VARCHAR(255) NOT NULL DEFAULT '',
                content      TEXT         NOT NULL DEFAULT '',
                CONSTRAINT fk_doc_trans_doc
                    FOREIGN KEY (documentation_id)
                    REFERENCES documentation(id)
                    ON DELETE CASCADE
            )
        SQL);

        // 2. Contrainte unique (doc, locale)
        $this->addSql(
            'CREATE UNIQUE INDEX uniq_doc_locale ON documentation_translation (documentation_id, locale)'
        );

        // 3. Migre title + content existants comme traductions FR
        $this->addSql(<<<'SQL'
            INSERT INTO documentation_translation (documentation_id, locale, title, content)
            SELECT id, 'fr', title, content
            FROM documentation
            WHERE title <> '' OR content <> ''
        SQL);

        // 4. Supprime les colonnes désormais inutiles
        $this->addSql('ALTER TABLE documentation DROP COLUMN title');
        $this->addSql('ALTER TABLE documentation DROP COLUMN content');
    }

    public function down(Schema $schema): void
    {
        // 1. Réintroduit les colonnes
        $this->addSql("ALTER TABLE documentation ADD COLUMN title   VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE documentation ADD COLUMN content TEXT         NOT NULL DEFAULT ''");

        // 2. Restaure les données FR
        $this->addSql(<<<'SQL'
            UPDATE documentation d
            SET title   = t.title,
                content = t.content
            FROM documentation_translation t
            WHERE t.documentation_id = d.id
              AND t.locale = 'fr'
        SQL);

        // 3. Supprime la table de traductions
        $this->addSql('DROP TABLE documentation_translation');
    }
}
