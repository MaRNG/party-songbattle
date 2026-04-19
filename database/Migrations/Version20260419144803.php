<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419144803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE artist (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, external_id VARCHAR(255) DEFAULT NULL, external_source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_15996879F75D7B0 (external_id), PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE genre (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, external_id VARCHAR(255) DEFAULT NULL, external_source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_835033F89F75D7B0 (external_id), PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE playlist (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, external_id VARCHAR(255) DEFAULT NULL, external_source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_D782112D9F75D7B0 (external_id), PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE track (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, external_id VARCHAR(255) DEFAULT NULL, external_source VARCHAR(255) DEFAULT NULL, external_popularity_score SMALLINT DEFAULT NULL, name VARCHAR(255) NOT NULL, release_year SMALLINT DEFAULT NULL, release_date DATETIME DEFAULT NULL, popularity_score SMALLINT DEFAULT NULL, duration_ms INT NOT NULL, UNIQUE INDEX UNIQ_D6E3F8A69F75D7B0 (external_id), PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE tracks_playlists (track_id INT NOT NULL, playlist_id INT NOT NULL, INDEX IDX_25CD23C95ED23C43 (track_id), INDEX IDX_25CD23C96BBD148 (playlist_id), PRIMARY KEY (track_id, playlist_id))');
        $this->addSql('CREATE TABLE tracks_artists (track_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_1B81E2955ED23C43 (track_id), INDEX IDX_1B81E295B7970CF8 (artist_id), PRIMARY KEY (track_id, artist_id))');
        $this->addSql('CREATE TABLE tracks_genres (track_id INT NOT NULL, genre_id INT NOT NULL, INDEX IDX_269FED915ED23C43 (track_id), INDEX IDX_269FED914296D31F (genre_id), PRIMARY KEY (track_id, genre_id))');
        $this->addSql('ALTER TABLE tracks_playlists ADD CONSTRAINT FK_25CD23C95ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_playlists ADD CONSTRAINT FK_25CD23C96BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_artists ADD CONSTRAINT FK_1B81E2955ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_artists ADD CONSTRAINT FK_1B81E295B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_genres ADD CONSTRAINT FK_269FED915ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_genres ADD CONSTRAINT FK_269FED914296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tracks_playlists DROP FOREIGN KEY FK_25CD23C95ED23C43');
        $this->addSql('ALTER TABLE tracks_playlists DROP FOREIGN KEY FK_25CD23C96BBD148');
        $this->addSql('ALTER TABLE tracks_artists DROP FOREIGN KEY FK_1B81E2955ED23C43');
        $this->addSql('ALTER TABLE tracks_artists DROP FOREIGN KEY FK_1B81E295B7970CF8');
        $this->addSql('ALTER TABLE tracks_genres DROP FOREIGN KEY FK_269FED915ED23C43');
        $this->addSql('ALTER TABLE tracks_genres DROP FOREIGN KEY FK_269FED914296D31F');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE genre');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE tracks_playlists');
        $this->addSql('DROP TABLE tracks_artists');
        $this->addSql('DROP TABLE tracks_genres');
    }
}
