<?php

namespace App\Model\Game\Dto;

final readonly class GameRoundResultDto
{
    public function __construct(
        public bool    $correct,
        public ?string $guesserName,
        public ?float  $atSeconds,
        public ?int    $points,
        public ?int    $streak,
        public ?int    $score,
    )
    {
    }
}
