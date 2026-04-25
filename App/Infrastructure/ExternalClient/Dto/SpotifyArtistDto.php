<?php

namespace App\Infrastructure\ExternalClient\Dto;

final readonly class SpotifyArtistDto
{
    public function __construct(
        public string $spotifyId,
        public string $name,
    )
    {
    }
}