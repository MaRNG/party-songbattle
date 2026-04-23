<?php

namespace App\Infrastructure\Import\Playlist;

use App\Infrastructure\ExternalClient\SpotifyExternalClient;

final readonly class SpotifyPlaylistImport
{
    public function __construct(
        private SpotifyExternalClient $spotifyExternalClient,
    )
    {
    }

    public function importPlaylistTracks(string $playlistId): void
    {
        $playlistDto = $this->spotifyExternalClient->playlists()->listTracks($playlistId);

        var_dump($playlistDto);
        die();
    }
}