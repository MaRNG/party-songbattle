<?php

namespace App\Infrastructure\ExternalClient\Dto;

final readonly class SpotifyAlbumDto
{
    /**
     * @param array<int, SpotifyArtistDto> $artists
     */
    public function __construct(
        public string              $spotifyId,
        public string              $name,
        public ?\DateTimeInterface $releaseDate,
        public ?int                $releaseYear,
        public array               $artists
    )
    {
    }
}