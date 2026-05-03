<?php

namespace App\Model\Statistics\Dto;

final readonly class StatisticsDto
{
    /**
     * @param int $trackCounts
     * @param array<string,int> $trackCountByDecades
     * @param array<string,int> $trackCountByArtists
     * @param array<string,int> $trackCountByAreas
     * @param array<string,int> $trackCountByGenres
     * @param array<string,int> $trackCountByTags
     */
    public function __construct(
        public int   $trackCounts,
        public array $trackCountByDecades,
        public array $trackCountByArtists,
        public array $trackCountByAreas,
        public array $trackCountByGenres,
        public array $trackCountByTags,
    )
    {
    }
}