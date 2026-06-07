<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\GameGuess;

final class GameGuessRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return GameGuess::class;
    }
}
