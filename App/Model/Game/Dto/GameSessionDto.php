<?php

namespace App\Model\Game\Dto;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GamePlayer;

final readonly class GameSessionDto
{
    public function __construct(
        public Game       $game,
        public GamePlayer $player,
    )
    {
    }
}
