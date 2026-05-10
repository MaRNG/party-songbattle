<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\Game;

final class GameRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Game::class;
    }
}