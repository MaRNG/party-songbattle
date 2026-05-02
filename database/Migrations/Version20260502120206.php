<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502120206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, external_source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE tracks_tags (track_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_320083D75ED23C43 (track_id), INDEX IDX_320083D7BAD26311 (tag_id), PRIMARY KEY (track_id, tag_id))');
        $this->addSql('ALTER TABLE tracks_tags ADD CONSTRAINT FK_320083D75ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_tags ADD CONSTRAINT FK_320083D7BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE artist ADD country VARCHAR(5) NOT NULL');
        $this->addSql('CREATE INDEX country_idx ON artist (country)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tracks_tags DROP FOREIGN KEY FK_320083D75ED23C43');
        $this->addSql('ALTER TABLE tracks_tags DROP FOREIGN KEY FK_320083D7BAD26311');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE tracks_tags');
        $this->addSql('DROP INDEX country_idx ON artist');
        $this->addSql('ALTER TABLE artist DROP country');
    }
}
