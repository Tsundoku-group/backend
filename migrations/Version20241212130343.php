<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241212130343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ALTER first_name DROP NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER last_name DROP NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER birthday DROP NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER gender DROP NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER phone_number DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE profile ALTER first_name SET NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER last_name SET NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER birthday SET NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER gender SET NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER phone_number SET NOT NULL');
    }
}
