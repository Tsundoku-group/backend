<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241224131125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation_profile (conversation_id INT NOT NULL, profile_id INT NOT NULL, PRIMARY KEY(conversation_id, profile_id))');
        $this->addSql('CREATE INDEX IDX_1A01A4059AC0396 ON conversation_profile (conversation_id)');
        $this->addSql('CREATE INDEX IDX_1A01A405CCFA12B8 ON conversation_profile (profile_id)');
        $this->addSql('ALTER TABLE conversation_profile ADD CONSTRAINT FK_1A01A4059AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_profile ADD CONSTRAINT FK_1A01A405CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_user DROP CONSTRAINT fk_5aecb5559ac0396');
        $this->addSql('ALTER TABLE conversation_user DROP CONSTRAINT fk_5aecb555a76ed395');
        $this->addSql('DROP TABLE conversation_user');
        $this->addSql('ALTER TABLE conversation DROP CONSTRAINT FK_8A8E26E9B03A8386');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B03A8386 FOREIGN KEY (created_by_id) REFERENCES profile (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friendship DROP CONSTRAINT FK_7234A45FED442CF4');
        $this->addSql('ALTER TABLE friendship DROP CONSTRAINT FK_7234A45FCD53EDB6');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_7234A45FED442CF4 FOREIGN KEY (requester_id) REFERENCES profile (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT FK_7234A45FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES profile (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE profile ALTER type SET NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER active_profile DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0FF85E0677 ON profile (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64935C246D5 ON "user" (password)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation_user (conversation_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(conversation_id, user_id))');
        $this->addSql('CREATE INDEX idx_5aecb555a76ed395 ON conversation_user (user_id)');
        $this->addSql('CREATE INDEX idx_5aecb5559ac0396 ON conversation_user (conversation_id)');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT fk_5aecb5559ac0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT fk_5aecb555a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_profile DROP CONSTRAINT FK_1A01A4059AC0396');
        $this->addSql('ALTER TABLE conversation_profile DROP CONSTRAINT FK_1A01A405CCFA12B8');
        $this->addSql('DROP TABLE conversation_profile');
        $this->addSql('ALTER TABLE friendship DROP CONSTRAINT fk_7234a45fed442cf4');
        $this->addSql('ALTER TABLE friendship DROP CONSTRAINT fk_7234a45fcd53edb6');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT fk_7234a45fed442cf4 FOREIGN KEY (requester_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friendship ADD CONSTRAINT fk_7234a45fcd53edb6 FOREIGN KEY (receiver_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation DROP CONSTRAINT fk_8a8e26e9b03a8386');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT fk_8a8e26e9b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX UNIQ_8157AA0FF85E0677');
        $this->addSql('ALTER TABLE profile ALTER type DROP NOT NULL');
        $this->addSql('ALTER TABLE profile ALTER active_profile SET NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D64935C246D5');
    }
}
