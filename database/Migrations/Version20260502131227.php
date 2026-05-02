<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502131227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE artist ADD music_brainz_imported TINYINT NOT NULL');
        $this->addSql('CREATE INDEX music_brainz_imported_idx ON artist (music_brainz_imported)');
        $this->addSql('ALTER TABLE track ADD music_brainz_imported TINYINT NOT NULL');
        $this->addSql('CREATE INDEX music_brainz_imported_idx ON track (music_brainz_imported)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX music_brainz_imported_idx ON artist');
        $this->addSql('ALTER TABLE artist DROP music_brainz_imported');
        $this->addSql('DROP INDEX music_brainz_imported_idx ON track');
        $this->addSql('ALTER TABLE track DROP music_brainz_imported');
    }
}
