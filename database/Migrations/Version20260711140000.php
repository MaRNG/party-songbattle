<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game.pending_reveal_track_position so the reveal always references the exact track it is about';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD pending_reveal_track_position INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP pending_reveal_track_position');
    }
}
