<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_player.kicked_at (master kick) and game.pending_reveal_started_at (ALL mode auto-continue timer)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_player ADD kicked_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD pending_reveal_started_at DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_player DROP kicked_at');
        $this->addSql('ALTER TABLE game DROP pending_reveal_started_at');
    }
}
