<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Album\Album;
use App\Infrastructure\Database\Entity\Track\Track;

final class AlbumRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Album::class;
    }
}