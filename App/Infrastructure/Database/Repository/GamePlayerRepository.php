<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GamePlayer;

final class GamePlayerRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return GamePlayer::class;
    }

    public function findByToken(string $token): ?GamePlayer
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * @return array<int,GamePlayer>
     */
    public function findByGame(Game $game): array
    {
        return $this->findBy(['game' => $game], ['id' => 'ASC']);
    }

    /**
     * Players still taking part in the game — excludes anyone the master has kicked.
     * Used everywhere a kicked player must disappear from view/rotation, while
     * `findByGame`/`countByGame` keep including them for join-time position/color
     * assignment, which doesn't need to change once someone leaves.
     *
     * @return array<int,GamePlayer>
     */
    public function findActiveByGame(Game $game): array
    {
        return $this->findBy(['game' => $game, 'kicked_at' => null], ['id' => 'ASC']);
    }

    public function countByGame(Game $game): int
    {
        return count($this->findByGame($game));
    }
}
