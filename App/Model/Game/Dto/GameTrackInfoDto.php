<?php

namespace App\Model\Game\Dto;

final readonly class GameTrackInfoDto
{
    public function __construct(
        public string  $trackName,
        public string  $artistName,
        public ?string $spotifyTrackId = null,
    )
    {
    }
}
