<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game.pending_reveal_* columns for the server-driven round reveal/continue flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD pending_reveal_correct TINYINT(1) DEFAULT NULL, ADD pending_reveal_guesser_name VARCHAR(40) DEFAULT NULL, ADD pending_reveal_at_seconds DOUBLE PRECISION DEFAULT NULL, ADD pending_reveal_points INT DEFAULT NULL, ADD pending_reveal_streak INT DEFAULT NULL, ADD pending_reveal_score INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP pending_reveal_correct, DROP pending_reveal_guesser_name, DROP pending_reveal_at_seconds, DROP pending_reveal_points, DROP pending_reveal_streak, DROP pending_reveal_score');
    }
}
