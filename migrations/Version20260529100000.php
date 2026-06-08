<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne avatar_color sur la table user.
 */
final class Version20260529100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_color column to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE \"user\" ADD COLUMN avatar_color VARCHAR(7) NOT NULL DEFAULT '#3B82F6'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN avatar_color');
    }
}
