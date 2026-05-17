<?php

namespace App\Util;

use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\Database\Entity\Track\Track;
use Nette\StaticClass;

final readonly class TrackHashGenerator
{
    use StaticClass;

    public static function generateTrackNameArtistNameHash(Track $track): string
    {
        $toHash = sprintf('%s-%s', $track->getName(), implode(', ', array_map(static function(Artist $artist)
        {
            return $artist->getName();
        }, $track->getArtists()->toArray())));

        return md5($toHash);
    }
}