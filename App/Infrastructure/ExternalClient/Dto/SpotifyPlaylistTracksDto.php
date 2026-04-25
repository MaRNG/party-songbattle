<?php

namespace App\Infrastructure\ExternalClient\Dto;

final readonly class SpotifyPlaylistTracksDto
{
    /**
     * @param array<int, SpotifyTrackDto> $items
     */
    public function __construct(
        public array   $items,
        public int     $total,
        public ?string $next
    )
    {
    }
}