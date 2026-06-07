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

    public function countByGame(Game $game): int
    {
        return count($this->findByGame($game));
    }
}
