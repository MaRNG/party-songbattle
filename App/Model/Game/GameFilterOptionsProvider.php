<?php

namespace App\Model\Game;

use App\Infrastructure\Database\Repository\GenreRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Model\Game\Dto\GameFilterListDto;
use App\Model\Game\Dto\GameFilterOptionsDto;

final readonly class GameFilterOptionsProvider
{
    public function __construct(
        private TrackRepository $trackRepository,
        private GenreRepository $genreRepository,
        private GameFactory     $gameFactory,
    )
    {
    }

    public function get(GameFilterListDto $appliedFilters): GameFilterOptionsDto
    {
        return new GameFilterOptionsDto(
            decades  : array_values($this->trackRepository->getDecadesFilterData()),
            genres   : $this->genreRepository->getSelectFormData(),
            areas    : $this->trackRepository->getAreaFilterData(),
            poolCount: $this->gameFactory->countMatchingTracks($appliedFilters),
        );
    }
}
