<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Genre\Genre;

final class GenreRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Genre::class;
    }
}