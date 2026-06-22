<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refonte des rôles ProjectMember :
 *   contributor → reader    (lecture seule + notes)
 *   editor      → contributor (écriture du contenu)
 *   lead        → moderator   (gestion du projet)
 */
final class Version20260609000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename project member roles: contributor→reader, editor→contributor, lead→moderator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE project_member SET role = 'moderator'   WHERE role = 'lead'");
        $this->addSql("UPDATE project_member SET role = 'contributor' WHERE role = 'editor'");
        $this->addSql("UPDATE project_member SET role = 'reader'      WHERE role = 'contributor'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE project_member SET role = 'lead'        WHERE role = 'moderator'");
        $this->addSql("UPDATE project_member SET role = 'editor'      WHERE role = 'contributor'");
        $this->addSql("UPDATE project_member SET role = 'contributor' WHERE role = 'reader'");
    }
}
