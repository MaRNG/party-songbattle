<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Track\Track;

final class TrackRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Track::class;
    }
}