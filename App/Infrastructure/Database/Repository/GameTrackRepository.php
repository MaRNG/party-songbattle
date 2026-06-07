<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameTrack;

final class GameTrackRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return GameTrack::class;
    }

    /**
     * @return array<int,GameTrack>
     */
    public function findByGame(Game $game): array
    {
        return $this->findBy(['game' => $game], ['id' => 'ASC']);
    }

    public function findAtPosition(Game $game, int $position): ?GameTrack
    {
        return $this->findByGame($game)[$position] ?? null;
    }

    public function countByGame(Game $game): int
    {
        return count($this->findByGame($game));
    }
}
