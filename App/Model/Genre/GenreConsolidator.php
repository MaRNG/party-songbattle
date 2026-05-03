<?php

namespace App\Model\Genre;

final class GenreConsolidator
{
    /**
     * Základní žánry, do kterých se budou tagy sjednocovat.
     * Můžete libovolně upravit nebo přidat další.
     */
    public const BASE_GENRES = [
        'pop',
        'rock',
        'metal',
        'rap',
        'hip hop',
        'jazz',
        'blues',
        'country',
        'electronic',
        'dance',
        'classical',
        'reggae',
        'folk',
        'r&b',
        'soul',
        'indie',
        'punk',
        'alternative',
    ];

    /**
     * Vrací sjednocené základní žánry podle pole tagů.
     *
     * @param string[] $tags
     * @return string[]
     */
    public function extractGenresFromTags(array $tags): array
    {
        $extractedGenres = [];

        foreach ($tags as $tag) {
            $tagLower = strtolower(trim($tag));

            foreach (self::BASE_GENRES as $baseGenre) {
                // Hledáme základní žánr uvnitř tagu
                if (str_contains($tagLower, strtolower($baseGenre))) {
                    $extractedGenres[] = $baseGenre;
                }
            }
        }

        return array_values(array_unique($extractedGenres));
    }
}
