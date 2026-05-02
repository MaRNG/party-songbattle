<?php

namespace App\Infrastructure\ExternalClient\Module;

use GuzzleHttp\Client;

abstract class BaseMusicBrainzHandler
{
    public function __construct(protected Client $client)
    {
    }
}