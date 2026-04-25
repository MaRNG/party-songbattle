<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425190330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE album (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, external_id VARCHAR(255) DEFAULT NULL, external_source VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_39986E439F75D7B0 (external_id), PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE tracks_albums (track_id INT NOT NULL, album_id INT NOT NULL, INDEX IDX_7A964FC85ED23C43 (track_id), INDEX IDX_7A964FC81137ABCF (album_id), PRIMARY KEY (track_id, album_id))');
        $this->addSql('ALTER TABLE tracks_albums ADD CONSTRAINT FK_7A964FC85ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tracks_albums ADD CONSTRAINT FK_7A964FC81137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tracks_albums DROP FOREIGN KEY FK_7A964FC85ED23C43');
        $this->addSql('ALTER TABLE tracks_albums DROP FOREIGN KEY FK_7A964FC81137ABCF');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE tracks_albums');
    }
}
