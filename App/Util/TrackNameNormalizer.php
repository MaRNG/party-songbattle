<?php

namespace App\Util;

use Nette\StaticClass;

final readonly class TrackNameNormalizer
{
    use StaticClass;

    public static function normalizeName(string $trackName): string
    {
        $normalizedTrackName = preg_replace(
            '/(?:\s*[\(\[]?\s*)' .
            '(?:' .
            '(?:feat\.?|ft\.?|with)\s+[^)\]\-]*' .                  // feat / ft / with
            '|' .
            '(?:Original\s+)?(?:Radio|Video)\s+(?:Edit|Version)' .  // Radio/Video Edit/Version
            '|' .
            'Short\s+Mix' .                                        // Short Mix
            '|' .
            'Original\s+[^)\]\-]*?\s+Version' .                     // Original * Version
            ')' .
            '(?:\s*[\)\]]?)' .
            '/i',
            '',
            $trackName
        );

        $normalizedTrackName = preg_replace('/\s*-\s*$/', '', $normalizedTrackName);
        $normalizedTrackName = preg_replace('/\s{2,}/', ' ', $normalizedTrackName);
        $normalizedTrackName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizedTrackName);
        $normalizedTrackName = mb_strtolower($normalizedTrackName);

        return trim($normalizedTrackName);
    }
}