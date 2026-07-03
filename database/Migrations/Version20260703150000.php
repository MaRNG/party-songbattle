<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game.points_per_step and game.show_leaderboard_to_players for master-configurable scoring/leaderboard settings';
    }

    public function up(Schema $schema): void
    {
        // JSON columns can't carry a literal DEFAULT on ADD COLUMN, so add nullable,
        // backfill existing rows, then tighten to NOT NULL to match the entity mapping.
        $this->addSql('ALTER TABLE game ADD points_per_step JSON DEFAULT NULL, ADD show_leaderboard_to_players TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('UPDATE game SET points_per_step = \'[500,300,200,100,75,50]\' WHERE points_per_step IS NULL');
        $this->addSql('ALTER TABLE game MODIFY points_per_step JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP points_per_step, DROP show_leaderboard_to_players');
    }
}
