<?php

namespace App\Infrastructure\ExternalClient;

use App\Infrastructure\ExternalClient\Module\BaseMusicBrainzHandler;
use App\Infrastructure\ExternalClient\Module\MusicBrainzArtistHandler;
use App\Infrastructure\ExternalClient\Module\MusicBrainzTrackHandler;
use GuzzleHttp\Client;

final class MusicBrainzExternalClient
{
    private const string API_URL = 'https://musicbrainz.org';

    /**
     * @var array<string,BaseMusicBrainzHandler>
     */
    private array $handlers = [];

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::API_URL,
            'headers'  => [
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function tracks(): MusicBrainzTrackHandler
    {
        return $this->getHandler(MusicBrainzTrackHandler::class);
    }

    public function artists(): MusicBrainzArtistHandler
    {
        return $this->getHandler(MusicBrainzArtistHandler::class);
    }

    /**
     * @template T as BaseMusicBrainzHandler
     * @param class-string<T> $handlerClassname
     * @return BaseMusicBrainzHandler
     */
    private function getHandler(string $handlerClassname): BaseMusicBrainzHandler
    {
        if (isset($this->handlers[$handlerClassname]))
        {
            return $this->handlers[$handlerClassname];
        }

        return $this->handlers[$handlerClassname] = new $handlerClassname($this->client);
    }
}