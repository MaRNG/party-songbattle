<?php

namespace App\Infrastructure\ExternalClient\Module;

use GuzzleHttp\Client;

abstract class BaseSpotifyHandler
{
    public function __construct(protected Client $client)
    {
    }
}