<?php

namespace App\Infrastructure\ExternalClient\Dto;

final readonly class MusicBrainzArtistDto
{
    public function __construct(
        public string  $id,
        public string  $name,
        public ?string $country,
        public ?string $area,
    )
    {
    }
}