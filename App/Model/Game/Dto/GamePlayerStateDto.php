<?php

namespace App\Model\Game\Dto;

use App\Model\Enum\GamePlayerRoleEnum;

final readonly class GamePlayerStateDto
{
    public function __construct(
        public int                $id,
        public string             $name,
        public string             $initials,
        public string             $color,
        public GamePlayerRoleEnum $role,
        public int                $score,
        public int                $streak,
        public int                $guesses,
        public bool               $connected,
        public bool               $isViewer,
        public bool               $isCurrentTurn,
        // ALL mode only — null for every other mode/status.
        public ?int               $attemptsRemaining = null,
        public ?bool              $answeredCorrectly = null,
        public ?bool              $hasPassed = null,
    )
    {
    }
}
