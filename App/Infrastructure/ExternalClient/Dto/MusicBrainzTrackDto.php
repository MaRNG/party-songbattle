<?php

namespace App\Infrastructure\ExternalClient\Dto;

final readonly class MusicBrainzTrackDto
{
    /**
     * @param array<int,string> $artistNames
     * @param array<int,string> $tags
     */
    public function __construct(
        public string $id,
        public string $name,
        public array  $artistNames,
        public array  $tags
    )
    {
    }
}