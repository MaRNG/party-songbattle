<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store game.playback_resumed_at as a float unix timestamp for sub-second precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE game SET playback_resumed_at = NULL');
        $this->addSql('ALTER TABLE game CHANGE playback_resumed_at playback_resumed_at DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE game SET playback_resumed_at = NULL');
        $this->addSql('ALTER TABLE game CHANGE playback_resumed_at playback_resumed_at DATETIME DEFAULT NULL');
    }
}
