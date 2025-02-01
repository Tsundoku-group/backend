<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201193203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "group" ADD visibility VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE "group" ADD created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE "group" ADD CONSTRAINT FK_6DC044C5B03A8386 FOREIGN KEY (created_by_id) REFERENCES "profile" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6DC044C5B03A8386 ON "group" (created_by_id)');
        $this->addSql('ALTER TABLE group_profile ADD role VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "group" DROP CONSTRAINT FK_6DC044C5B03A8386');
        $this->addSql('DROP INDEX IDX_6DC044C5B03A8386');
        $this->addSql('ALTER TABLE "group" DROP visibility');
        $this->addSql('ALTER TABLE "group" DROP created_by_id');
        $this->addSql('ALTER TABLE group_profile DROP role');
    }
}
