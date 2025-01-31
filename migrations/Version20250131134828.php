<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131134828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'group_visibility') THEN CREATE TYPE group_visibility AS ENUM ('public', 'private'); END IF; END $$");

        $this->addSql('ALTER TABLE "group" ALTER COLUMN visibility TYPE group_visibility USING visibility::group_visibility');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "group" ALTER COLUMN visibility TYPE VARCHAR(255) USING visibility::text');

        $this->addSql('DROP TYPE group_visibility');
    }
}
