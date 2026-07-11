<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_guess.passed for the ALL mode "I don\'t know" skip action';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_guess ADD passed TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_guess DROP passed');
    }
}
