<?php

namespace App\Model\Game\Dto;

final readonly class GameFilterOptionsDto
{
    /**
     * @param array<int,int> $decades
     * @param array<int,string> $genres
     * @param array<string,string> $areas
     */
    public function __construct(
        public array $decades,
        public array $genres,
        public array $areas,
        public int   $poolCount,
    )
    {
    }
}
