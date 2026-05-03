<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Tag\Tag;

final class TagRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Tag::class;
    }
}