<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remplace le champ isPublic (bool) par visibility (string 20)
 * sur la table project, et ajoute is_public sur scenario_element
 * pour la publication épisode par épisode (séries).
 */
final class Version20260528200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Project visibility 3-state (unpublished/private/public) + ScenarioElement.isPublic for episodes';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter la colonne visibility avec valeur par défaut
        $this->addSql("ALTER TABLE project ADD visibility VARCHAR(20) NOT NULL DEFAULT 'unpublished'");

        // 2. Migrer les données existantes
        //    is_public = true  → visibility = 'public'
        //    is_public = false → visibility = 'unpublished'
        $this->addSql("UPDATE project SET visibility = 'public' WHERE is_public = true");

        // 3. Supprimer l'ancienne colonne is_public
        $this->addSql('ALTER TABLE project DROP COLUMN is_public');

        // 4. Ajouter is_public sur scenario_element pour les épisodes
        $this->addSql('ALTER TABLE scenario_element ADD is_public BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // Restaurer is_public sur project
        $this->addSql('ALTER TABLE project ADD is_public BOOLEAN NOT NULL DEFAULT false');
        $this->addSql("UPDATE project SET is_public = true WHERE visibility = 'public'");
        $this->addSql('ALTER TABLE project DROP COLUMN visibility');

        // Supprimer is_public de scenario_element
        $this->addSql('ALTER TABLE scenario_element DROP COLUMN is_public');
    }
}
