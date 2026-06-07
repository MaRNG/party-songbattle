<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Song Battle game session state (mode, status, playback) and GamePlayer / GameGuess entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD mode VARCHAR(255) NOT NULL, ADD status VARCHAR(255) NOT NULL, ADD current_track_position INT NOT NULL, ADD current_step_index INT NOT NULL, ADD elapsed_seconds DOUBLE PRECISION NOT NULL, ADD playback_resumed_at DATETIME DEFAULT NULL, ADD current_turn_position INT NOT NULL');

        $this->addSql('CREATE TABLE game_player (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, token VARCHAR(64) NOT NULL, name VARCHAR(40) NOT NULL, initials VARCHAR(4) NOT NULL, color VARCHAR(16) NOT NULL, role VARCHAR(255) NOT NULL, score INT NOT NULL, streak INT NOT NULL, guesses INT NOT NULL, connected TINYINT NOT NULL, last_seen DATETIME NOT NULL, game_id INT NOT NULL, UNIQUE INDEX UNIQ_GAME_PLAYER_TOKEN (token), INDEX game_player_token_idx (token), INDEX IDX_GAME_PLAYER_GAME (game_id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_GAME_PLAYER_GAME FOREIGN KEY (game_id) REFERENCES game (id)');

        $this->addSql('CREATE TABLE game_guess (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, correct TINYINT NOT NULL, at_seconds DOUBLE PRECISION NOT NULL, points INT NOT NULL, game_id INT NOT NULL, game_track_id INT NOT NULL, game_player_id INT NOT NULL, INDEX IDX_GAME_GUESS_GAME (game_id), INDEX IDX_GAME_GUESS_GAME_TRACK (game_track_id), INDEX IDX_GAME_GUESS_GAME_PLAYER (game_player_id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE game_guess ADD CONSTRAINT FK_GAME_GUESS_GAME FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE game_guess ADD CONSTRAINT FK_GAME_GUESS_GAME_TRACK FOREIGN KEY (game_track_id) REFERENCES game_track (id)');
        $this->addSql('ALTER TABLE game_guess ADD CONSTRAINT FK_GAME_GUESS_GAME_PLAYER FOREIGN KEY (game_player_id) REFERENCES game_player (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_guess DROP FOREIGN KEY FK_GAME_GUESS_GAME');
        $this->addSql('ALTER TABLE game_guess DROP FOREIGN KEY FK_GAME_GUESS_GAME_TRACK');
        $this->addSql('ALTER TABLE game_guess DROP FOREIGN KEY FK_GAME_GUESS_GAME_PLAYER');
        $this->addSql('DROP TABLE game_guess');

        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_GAME_PLAYER_GAME');
        $this->addSql('DROP TABLE game_player');

        $this->addSql('ALTER TABLE game DROP mode, DROP status, DROP current_track_position, DROP current_step_index, DROP elapsed_seconds, DROP playback_resumed_at, DROP current_turn_position');
    }
}
