<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250224111057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notifications (id UUID NOT NULL, type VARCHAR(50) NOT NULL, resource_id UUID DEFAULT NULL, is_read BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, recipient_id INT NOT NULL, profile_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_notifications_recipient ON notifications (recipient_id)');
        $this->addSql('CREATE INDEX idx_notifications_profile ON notifications (profile_id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES "profile" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3CCFA12B8 FOREIGN KEY (profile_id) REFERENCES "profile" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post ALTER type TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3E92F8F78');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3CCFA12B8');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('ALTER TABLE post ALTER type TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE post ALTER type DROP NOT NULL');
    }
}
