<?php

namespace App\Model\Game\Dto;

final readonly class GameGuessResultDto
{
    public function __construct(
        public bool  $correct,
        public float $atSeconds,
        public int   $points,
        public int   $score,
        public int   $streak,
    )
    {
    }
}
