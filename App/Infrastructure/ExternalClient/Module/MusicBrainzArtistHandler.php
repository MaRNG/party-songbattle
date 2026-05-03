<?php

namespace App\Infrastructure\ExternalClient\Module;

use App\Infrastructure\ExternalClient\Dto\MusicBrainzArtistDto;
use App\Infrastructure\ExternalClient\Dto\MusicBrainzTrackDto;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;

final class MusicBrainzArtistHandler extends BaseMusicBrainzHandler
{
    public function findArtist(string $artistName): ?MusicBrainzArtistDto
    {
        $response = $this->client->get('/ws/2/artist/', [
            RequestOptions::QUERY => [
                'query' => sprintf('artist:%s', $artistName),
                'fmt' => 'json',
            ]
        ]);

        $jsonResponse = Json::decode($response->getBody()->getContents(), forceArrays: true);

        if ($jsonResponse['artists'] === [])
        {
            return null;
        }

        foreach ($jsonResponse['artists'] as $artist)
        {
            if (mb_strtolower($artist['name']) === mb_strtolower($artistName))
            {
                return new MusicBrainzArtistDto(
                    $artist['id'],
                    $artist['name'],
                    $artist['country'] ?? null,
                    $artist['area']['name'] ?? null,
                );
            }
        }

        return null;
    }
}