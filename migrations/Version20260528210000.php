<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute le champ `type` sur la table notification.
 */
final class Version20260528210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type field to notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE notification ADD type VARCHAR(30) NOT NULL DEFAULT 'info'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP COLUMN type');
    }
}
