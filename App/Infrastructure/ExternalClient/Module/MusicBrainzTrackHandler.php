<?php

namespace App\Infrastructure\ExternalClient\Module;

use App\Infrastructure\ExternalClient\Dto\MusicBrainzTrackDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyAlbumDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyArtistDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyPlaylistTracksDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyTrackDto;
use App\Util\TrackNameNormalizer;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;

final class MusicBrainzTrackHandler extends BaseMusicBrainzHandler
{
    public function findTrack(string $trackName, array $artistNames): ?MusicBrainzTrackDto
    {
        try {
            $response = $this->client->get('/ws/2/recording/', [
                RequestOptions::QUERY => [
                    'query' => sprintf('recording:%s AND artist:%s', $trackName, implode(', ', $artistNames)),
                    'fmt' => 'json',
                    'inc' => 'tags+artists'
                ]
            ]);
        } catch (\Exception $ex) {
            return null;
        }

        $jsonResponse = Json::decode($response->getBody()->getContents(), forceArrays: true);

        if ($jsonResponse['recordings'] === [])
        {
            return null;
        }

        $finalTrackId = null;
        $finalTrackName = null;
        $finalTrackTags = [];

        foreach ($jsonResponse['recordings'] as $recording)
        {
            $musicBrainzRecordingCleanedName = TrackNameNormalizer::normalizeName($recording['title']);
            $databaseRecordingCleanedName = TrackNameNormalizer::normalizeName($trackName);

            $musicBrainzArtistCleanedName = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($recording['artist-credit'][0]['name'] ?? '')));

            $databaseArtistCleanedNames = array_map(static function(string $artistName)
            {
                return mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $artistName));
            }, $artistNames);

            if (
                $finalTrackId === null &&
                $finalTrackName === null &&
                $musicBrainzRecordingCleanedName === $databaseRecordingCleanedName &&
                in_array($musicBrainzArtistCleanedName, $databaseArtistCleanedNames, true)
            )
            {
                $finalTrackId = $recording['id'];
                $finalTrackName = $recording['title'];
            }

            if (
                $musicBrainzRecordingCleanedName === $databaseRecordingCleanedName &&
                in_array($musicBrainzArtistCleanedName, $databaseArtistCleanedNames, true)
            )
            {
                foreach ($recording['tags'] as $tag)
                {
                    $finalTrackTags[] = $tag['name'];
                }
            }

        }

        if ($finalTrackId === null)
        {
            return null;
        }

        return new MusicBrainzTrackDto(
            $finalTrackId,
            $finalTrackName,
            $artistNames,
            array_unique($finalTrackTags)
        );
    }
}