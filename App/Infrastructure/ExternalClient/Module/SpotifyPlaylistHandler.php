<?php

namespace App\Infrastructure\ExternalClient\Module;

use App\Infrastructure\ExternalClient\Dto\SpotifyAlbumDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyArtistDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyPlaylistTracksDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyTrackDto;
use Nette\Utils\Json;

final class SpotifyPlaylistHandler extends BaseSpotifyHandler
{
    public function listTracks(string $playlistId, int $limit = 10, int $offset = 0): SpotifyPlaylistTracksDto
    {
        $response = $this->client->get('/v1/playlists/' . $playlistId . '/items', [
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
                'market' => 'CZ'
            ]
        ]);

        $parsedPlaylistTracks = Json::decode($response->getBody()->getContents(), forceArrays: true);
        $spotifyPlaylistTracks = self::mapListTracksResponse($parsedPlaylistTracks);

        $items = $spotifyPlaylistTracks->items;

        while ($spotifyPlaylistTracks->next)
        {
            $response = $this->client->get($spotifyPlaylistTracks->next);

            $parsedPlaylistTracks = Json::decode($response->getBody()->getContents(), forceArrays: true);
            $spotifyPlaylistTracks = self::mapListTracksResponse($parsedPlaylistTracks);

            foreach ($spotifyPlaylistTracks->items as $item)
            {
                $items[] = $item;
            }
        }

        return new SpotifyPlaylistTracksDto(
            items: $items,
            total: count($items),
            next: null
        );
    }

    private static function mapListTracksResponse(array $parsedPlaylistTracks): SpotifyPlaylistTracksDto
    {
        return new SpotifyPlaylistTracksDto(
            items: array_map(function (array $playlistTrackRow) {
                     $playlistTrack = $playlistTrackRow['item'];

                     return new SpotifyTrackDto(
                         spotifyId: $playlistTrack['id'],
                         name: $playlistTrack['name'],
                         releaseDate: \DateTime::createFromFormat('Y-m-d', $playlistTrack['album']['release_date']) ?: null,
                         releaseYear: \DateTime::createFromFormat('Y-m-d', $playlistTrack['album']['release_date']) ? (int)\DateTime::createFromFormat('Y-m-d', $playlistTrack['album']['release_date'])->format('Y') : (int)$playlistTrack['album']['release_date'],
                         durationMsS: $playlistTrack['duration_ms'],
                         artists: array_map(function (array $artist) {
                            return new SpotifyArtistDto(
                                spotifyId: $artist['id'],
                                name: $artist['name'],
                            );
                         }, $playlistTrack['artists']),
                         album: new SpotifyAlbumDto(
                             spotifyId: $playlistTrack['album']['id'],
                             name: $playlistTrack['album']['name'],
                             releaseDate: \DateTime::createFromFormat('Y-m-d', $playlistTrack['album']['release_date']) ?: null,
                             releaseYear: \DateTime::createFromFormat('Y-m-d', $playlistTrack['album']['release_date']) ? (int)\DateTime::createFromFormat('Y-m-d', $playlistTrack['album']['release_date'])->format('Y') : (int)$playlistTrack['album']['release_date'],
                             artists: array_map(function (array $artist) {
                                return new SpotifyArtistDto(
                                    spotifyId: $artist['id'],
                                    name: $artist['name'],
                                );
                            }, $playlistTrack['artists']),
                        ),
                     );
                 }, $parsedPlaylistTracks['items']),
            total: (int)$parsedPlaylistTracks['total'],
            next : $parsedPlaylistTracks['next'],
        );
    }
}