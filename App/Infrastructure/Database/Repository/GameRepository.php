<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\Game;

final class GameRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Game::class;
    }

    public function findByHash(string $gameHash): ?Game
    {
        return $this->findOneBy(['hash' => $gameHash]);
    }

    public function findByInviteCode(string $inviteCode): ?Game
    {
        return $this->findOneBy(['code' => $inviteCode]);
    }
}