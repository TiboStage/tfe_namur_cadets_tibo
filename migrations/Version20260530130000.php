<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute has_content sur scenario_element.
 * Peuplé depuis project_type_config (jointure project_id + depth).
 * Corrige isLeaf() qui était hardcodé à elementType='scene'.
 */
final class Version20260530130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute has_content à scenario_element et le peuple depuis project_type_config.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE scenario_element ADD COLUMN has_content BOOLEAN NOT NULL DEFAULT FALSE'
        );

        // Peuple depuis la config : is leaf = le niveau le plus profond du projet
        $this->addSql(<<<'SQL'
            UPDATE scenario_element se
            SET has_content = TRUE
            FROM project_type_config ptc
            WHERE ptc.project_id = se.project_id
              AND ptc.depth      = se.depth
              AND ptc.has_content = TRUE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE scenario_element DROP COLUMN has_content');
    }
}
