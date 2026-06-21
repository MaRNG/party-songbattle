<?php

namespace App\Model\Game\Dto;

use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GamePlayerRoleEnum;
use App\Model\Enum\GameStatusEnum;

final readonly class GameStateDto
{
    /**
     * @param array<int,GamePlayerStateDto> $players
     */
    public function __construct(
        public string             $code,
        public string             $hash,
        public GameModeEnum       $mode,
        public GameStatusEnum     $status,
        public GamePlayerRoleEnum $viewerRole,
        public bool               $isPlaying,
        public float              $elapsedSeconds,
        public float              $stepSeconds,
        public int                $stepIndex,
        public int                $totalSteps,
        public int                $trackPosition,
        public int                $totalTracks,
        public ?GameTrackInfoDto  $track,
        public ?string            $spotifyTrackId,
        public array              $players,
    )
    {
    }
}
