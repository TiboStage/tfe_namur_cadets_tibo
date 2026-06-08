<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Chronologie v2 — ajout event_type + linked_scene_id sur world_event.
 */
final class Version20260604120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Chronologie v2 : ajout event_type (plot/character/world/conflict/reveal) et linked_scene_id sur world_event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE world_event ADD COLUMN event_type VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE world_event ADD COLUMN linked_scene_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE world_event ADD CONSTRAINT fk_world_event_scene FOREIGN KEY (linked_scene_id) REFERENCES scenario_element(id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE world_event DROP CONSTRAINT fk_world_event_scene');
        $this->addSql('ALTER TABLE world_event DROP COLUMN linked_scene_id');
        $this->addSql('ALTER TABLE world_event DROP COLUMN event_type');
    }
}
