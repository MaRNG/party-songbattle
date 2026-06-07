<?php

namespace App\Infrastructure\Database\Entity\Game;

use App\Infrastructure\Database\Entity\BaseEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
class GameGuess extends BaseEntity
{
    #[ManyToOne(targetEntity: Game::class)]
    #[JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false)]
    protected ?Game $game = null;

    #[ManyToOne(targetEntity: GameTrack::class)]
    #[JoinColumn(name: 'game_track_id', referencedColumnName: 'id', nullable: false)]
    protected ?GameTrack $game_track = null;

    #[ManyToOne(targetEntity: GamePlayer::class)]
    #[JoinColumn(name: 'game_player_id', referencedColumnName: 'id', nullable: false)]
    protected ?GamePlayer $player = null;

    #[Column(type: 'boolean', nullable: false)]
    protected bool $correct;

    #[Column(type: 'float', nullable: false)]
    protected float $at_seconds;

    #[Column(type: 'integer', nullable: false)]
    protected int $points = 0;

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): GameGuess
    {
        $this->game = $game;
        return $this;
    }

    public function getGameTrack(): ?GameTrack
    {
        return $this->game_track;
    }

    public function setGameTrack(?GameTrack $game_track): GameGuess
    {
        $this->game_track = $game_track;
        return $this;
    }

    public function getPlayer(): ?GamePlayer
    {
        return $this->player;
    }

    public function setPlayer(?GamePlayer $player): GameGuess
    {
        $this->player = $player;
        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->correct;
    }

    public function setCorrect(bool $correct): GameGuess
    {
        $this->correct = $correct;
        return $this;
    }

    public function getAtSeconds(): float
    {
        return $this->at_seconds;
    }

    public function setAtSeconds(float $at_seconds): GameGuess
    {
        $this->at_seconds = $at_seconds;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): GameGuess
    {
        $this->points = $points;
        return $this;
    }
}
