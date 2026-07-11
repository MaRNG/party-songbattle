<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add track.audio_youtube_url, track.audio_file_path, track.audio_downloaded';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track ADD audio_youtube_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE track ADD audio_file_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE track ADD audio_downloaded TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track DROP audio_youtube_url');
        $this->addSql('ALTER TABLE track DROP audio_file_path');
        $this->addSql('ALTER TABLE track DROP audio_downloaded');
    }
}
