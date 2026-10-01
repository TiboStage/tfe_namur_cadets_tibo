<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928130007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute user.is_verified (confirmation d\'email) — les comptes existants sont considérés comme vérifiés';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD is_verified BOOLEAN DEFAULT false NOT NULL');
        // Comptes créés avant la vérification d'email : on ne les bloque pas à la connexion
        $this->addSql('UPDATE "user" SET is_verified = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP is_verified');
    }
}
