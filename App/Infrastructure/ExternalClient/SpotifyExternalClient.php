<?php

namespace App\Infrastructure\ExternalClient;

use App\Infrastructure\ExternalClient\Module\BaseSpotifyHandler;
use App\Infrastructure\ExternalClient\Module\SpotifyArtistHandler;
use App\Infrastructure\ExternalClient\Module\SpotifyPlaylistHandler;
use GuzzleHttp\Client;

final class SpotifyExternalClient
{
    private const string API_URL = 'https://api.spotify.com';

    /**
     * @var array<string,BaseSpotifyHandler>
     */
    private array $handlers = [];

    private Client $client;

    public function __construct(
        string $accessToken
    )
    {
        $this->client = new Client([
            'base_uri' => self::API_URL,
            'headers'  => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);
    }

    public function playlists(): SpotifyPlaylistHandler
    {
        return $this->getHandler(SpotifyPlaylistHandler::class);
    }

    public function artists(): SpotifyArtistHandler
    {
        return $this->getHandler(SpotifyArtistHandler::class);
    }

    /**
     * @template T as BaseSpotifyHandler
     * @param class-string<T> $handlerClassname
     * @return T
     */
    private function getHandler(string $handlerClassname): BaseSpotifyHandler
    {
        if (isset($this->handlers[$handlerClassname]))
        {
            return $this->handlers[$handlerClassname];
        }

        return $this->handlers[$handlerClassname] = new $handlerClassname($this->client);
    }
}