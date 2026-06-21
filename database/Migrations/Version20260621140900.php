<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621140900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_guess RENAME INDEX idx_game_guess_game TO IDX_E07619E48FD905');
        $this->addSql('ALTER TABLE game_guess RENAME INDEX idx_game_guess_game_track TO IDX_E07619F596C365');
        $this->addSql('ALTER TABLE game_guess RENAME INDEX idx_game_guess_game_player TO IDX_E076194B4034DD');
        $this->addSql('ALTER TABLE game_player RENAME INDEX uniq_game_player_token TO UNIQ_E52CD7AD5F37A13B');
        $this->addSql('ALTER TABLE game_player RENAME INDEX idx_game_player_game TO IDX_E52CD7ADE48FD905');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_guess RENAME INDEX idx_e076194b4034dd TO IDX_GAME_GUESS_GAME_PLAYER');
        $this->addSql('ALTER TABLE game_guess RENAME INDEX idx_e07619e48fd905 TO IDX_GAME_GUESS_GAME');
        $this->addSql('ALTER TABLE game_guess RENAME INDEX idx_e07619f596c365 TO IDX_GAME_GUESS_GAME_TRACK');
        $this->addSql('ALTER TABLE game_player RENAME INDEX uniq_e52cd7ad5f37a13b TO UNIQ_GAME_PLAYER_TOKEN');
        $this->addSql('ALTER TABLE game_player RENAME INDEX idx_e52cd7ade48fd905 TO IDX_GAME_PLAYER_GAME');
    }
}
