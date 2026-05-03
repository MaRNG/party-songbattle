<?php

namespace App\Infrastructure\ExternalClient\Module;

use App\Infrastructure\ExternalClient\Dto\MusicBrainzTrackDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyAlbumDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyArtistDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyPlaylistTracksDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyTrackDto;
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
            if (
                $finalTrackId === null &&
                $finalTrackName === null &&
                mb_strtolower($recording['title']) === strtolower($trackName) &&
                (mb_strtolower(($recording['artist-credit'][0]['name'] ?? '')) === mb_strtolower(($artistNames[0] ?? '')))
            )
            {
                $finalTrackId = $recording['id'];
                $finalTrackName = $recording['title'];
            }

            if (
                mb_strtolower($recording['title']) === mb_strtolower($trackName) &&
                (mb_strtolower(($recording['artist-credit'][0]['name'] ?? '')) === mb_strtolower(($artistNames[0] ?? '')))
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