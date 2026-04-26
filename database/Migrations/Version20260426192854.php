<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426192854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, code VARCHAR(255) NOT NULL, filters JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE game_track (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, track_name VARCHAR(255) NOT NULL, artist_name VARCHAR(255) NOT NULL, normalized_complete_name VARCHAR(255) NOT NULL, origin_track_id INT NOT NULL, game_id INT NOT NULL, INDEX IDX_E4D081296D6456B3 (origin_track_id), INDEX IDX_E4D08129E48FD905 (game_id), INDEX track_name_idx (track_name), INDEX artist_name_idx (artist_name), INDEX normalized_complete_name_idx (normalized_complete_name), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE game_track ADD CONSTRAINT FK_E4D081296D6456B3 FOREIGN KEY (origin_track_id) REFERENCES track (id)');
        $this->addSql('ALTER TABLE game_track ADD CONSTRAINT FK_E4D08129E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_track DROP FOREIGN KEY FK_E4D081296D6456B3');
        $this->addSql('ALTER TABLE game_track DROP FOREIGN KEY FK_E4D08129E48FD905');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_track');
    }
}
