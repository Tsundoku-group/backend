<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250224130044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE react (id UUID NOT NULL, react_type VARCHAR(50) NOT NULL, resource_type VARCHAR(50) NOT NULL, resource_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, actor_id INT NOT NULL, receiver_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_19656FD510DAF24A ON react (actor_id)');
        $this->addSql('CREATE INDEX IDX_19656FD5CD53EDB6 ON react (receiver_id)');
        $this->addSql('ALTER TABLE react ADD CONSTRAINT FK_19656FD510DAF24A FOREIGN KEY (actor_id) REFERENCES "profile" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE react ADD CONSTRAINT FK_19656FD5CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES "profile" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE post ALTER type TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE react DROP CONSTRAINT FK_19656FD510DAF24A');
        $this->addSql('ALTER TABLE react DROP CONSTRAINT FK_19656FD5CD53EDB6');
        $this->addSql('DROP TABLE react');
        $this->addSql('ALTER TABLE post ALTER type TYPE VARCHAR(255)');
    }
}
