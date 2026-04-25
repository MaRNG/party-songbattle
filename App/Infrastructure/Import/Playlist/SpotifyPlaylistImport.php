<?php

namespace App\Infrastructure\Import\Playlist;

use App\Infrastructure\Database\Entity\Album\Album;
use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\ExternalClient\Dto\SpotifyArtistDto;
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

        foreach ($playlistDto->items as $spotifyTrackDto)
        {
            var_dump($spotifyTrackDto);
            die();
        }
    }

    private function getOrCreateArtist(SpotifyArtistDto $spotifyArtistDto): Artist
    {

    }

    private function getOrCreateAlbum(SpotifyArtistDto $spotifyArtistDto): Album
    {

    }
}