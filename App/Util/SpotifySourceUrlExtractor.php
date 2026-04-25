<?php

namespace App\Util;

use Nette\StaticClass;

final readonly class SpotifySourceUrlExtractor
{
    use StaticClass;

    public static function extractPlaylistId(string $url): ?string
    {
        // https://open.spotify.com/playlist/5KdVA33mTQ4VYafceUdPs2?si=e1acec8398c042d8

        preg_match('/^https:\/\/open.spotify.com\/playlist\/(\S+)[\?|\/].+$/', $url, $matches);

        return $matches[1] ?? null;
    }
}