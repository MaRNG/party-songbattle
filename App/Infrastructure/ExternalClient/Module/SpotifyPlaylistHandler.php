<?php

namespace App\Infrastructure\ExternalClient\Module;

final class SpotifyPlaylistHandler extends BaseSpotifyHandler
{
    public function listTracks(string $playlistId, int $limit = 10, int $offset = 0): array
    {
        // https://api.spotify.com/v1/playlists/3cEYpjA9oz9GiPac4AsH4n/items?market=CZ&limit=10&offset=0
        $response = $this->client->get('/v1/playlists/' . $playlistId . '/items', [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
                'market' => 'CZ'
            ]
        ]);

        var_dump($response->getBody()->getContents());
        die();
    }
}