<?php

namespace App\Model\Game\Dto;

final readonly class GameFilterListDto
{
    public function __construct(
        public array $year_filter,
        public array $genre_filter,
        public array $area_filter,
        public array $artist_filter,
    )
    {
    }
}