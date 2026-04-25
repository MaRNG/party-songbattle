<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\Database\Entity\Track\Track;

final class ArtistRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Artist::class;
    }
}