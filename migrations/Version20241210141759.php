<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210141759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ajouter une colonne "active_profile" avec un type BOOLEAN et une valeur par défaut false
        $this->addSql('ALTER TABLE profile ADD active_profile BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la colonne ajoutée lors du rollback
        $this->addSql('ALTER TABLE profile DROP COLUMN active_profile');
    }
}
