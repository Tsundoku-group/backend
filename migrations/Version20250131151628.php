<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131151628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 🔹 Vérifier et créer le type ENUM group_role en PostgreSQL s'il n'existe pas déjà
        $this->addSql("DO $$ 
        BEGIN 
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'group_role') 
            THEN CREATE TYPE group_role AS ENUM ('admin', 'member'); 
            END IF; 
        END $$;"
        );

        // 🔹 Vérifier et créer le type ENUM group_visibility en PostgreSQL s'il n'existe pas déjà
        $this->addSql("DO $$ 
        BEGIN 
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'group_visibility') 
            THEN CREATE TYPE group_visibility AS ENUM ('public', 'private'); 
            END IF; 
        END $$;"
        );

        // 🔹 Création de la table group_profile (après création du type group_role)
        $this->addSql('CREATE TABLE group_profile (
        role group_role NOT NULL, 
        join_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
        is_banned BOOLEAN NOT NULL, 
        updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
        group_id INT NOT NULL, 
        profile_id INT NOT NULL, 
        PRIMARY KEY(group_id, profile_id)
    )');

        // 🔹 Ajout des index et contraintes
        $this->addSql('CREATE INDEX IDX_757FE03FE54D947 ON group_profile (group_id)');
        $this->addSql('CREATE INDEX IDX_757FE03CCFA12B8 ON group_profile (profile_id)');
        $this->addSql('ALTER TABLE group_profile ADD CONSTRAINT FK_757FE03FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_profile ADD CONSTRAINT FK_757FE03CCFA12B8 FOREIGN KEY (profile_id) REFERENCES "profile" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // 🔹 Ajout de la colonne visibility dans "group"
        $this->addSql('ALTER TABLE "group" ADD visibility group_visibility NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_profile DROP CONSTRAINT FK_757FE03FE54D947');
        $this->addSql('ALTER TABLE group_profile DROP CONSTRAINT FK_757FE03CCFA12B8');
        $this->addSql('DROP TABLE group_profile');
        $this->addSql('ALTER TABLE "group" DROP visibility');
    }
}
