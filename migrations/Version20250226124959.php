<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250226124959 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id UUID NOT NULL, notification_type VARCHAR(50) NOT NULL, resource_id VARCHAR(255) DEFAULT NULL, resource_type VARCHAR(255) NOT NULL, is_read BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, actor_id INT NOT NULL, receiver_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT fk_6000b0d310daf24a');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT fk_6000b0d3cd53edb6');
        $this->addSql('DROP TABLE notifications');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notifications (id UUID NOT NULL, type VARCHAR(50) NOT NULL, resource_id VARCHAR(255) DEFAULT NULL, is_read BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, actor_id INT NOT NULL, receiver_id INT NOT NULL, resource_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('DROP TABLE notification');
    }
}
