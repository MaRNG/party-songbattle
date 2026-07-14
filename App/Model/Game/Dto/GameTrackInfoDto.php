<?php

namespace App\Model\Game\Dto;

final readonly class GameTrackInfoDto
{
    public function __construct(
        public string  $trackName,
        public string  $artistName,
        public ?int    $audioTrackId = null,
        public ?int    $id = null,
    )
    {
    }
}
