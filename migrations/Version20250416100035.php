<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416100035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "group" ADD rules JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE "group" ADD activities JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE "group" ADD who_can_join VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "group" ADD external_links JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "group" DROP rules');
        $this->addSql('ALTER TABLE "group" DROP activities');
        $this->addSql('ALTER TABLE "group" DROP who_can_join');
        $this->addSql('ALTER TABLE "group" DROP external_links');
    }
}
