<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameGuess;
use App\Infrastructure\Database\Entity\Game\GameTrack;

final class GameGuessRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return GameGuess::class;
    }

    /**
     * All guesses (any player, correct or not) for a single track within a game, ordered
     * by insertion order — in ALL mode this order doubles as "who answered first", since
     * guesses are persisted synchronously as each request is handled.
     *
     * @return array<int,GameGuess>
     */
    public function findForTrack(Game $game, GameTrack $track): array
    {
        return $this->findBy(['game' => $game, 'game_track' => $track], ['id' => 'ASC']);
    }
}
