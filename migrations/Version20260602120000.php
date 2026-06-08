<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table de jointure user_likes.
 *
 * Permet à un utilisateur d'aimer le profil d'un autre créateur.
 * La relation est owning-side depuis $likedBy dans User.
 *
 *   liked_id  → id du créateur qui reçoit le like
 *   liker_id  → id de l'utilisateur qui like
 */
final class Version20260602120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table user_likes (système de likes entre créateurs)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE user_likes (
                liked_id  INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
                liker_id  INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
                PRIMARY KEY (liked_id, liker_id)
            )
        ');

        $this->addSql('CREATE INDEX idx_user_likes_liked  ON user_likes (liked_id)');
        $this->addSql('CREATE INDEX idx_user_likes_liker  ON user_likes (liker_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_likes');
    }
}
