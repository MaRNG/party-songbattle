<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Track\Track;

final class TrackRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return Track::class;
    }

    public function getDecadesFilterData(): array
    {
        $fetchedData = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT FLOOR(release_year / 10) * 10 AS decade FROM track GROUP BY decade ORDER BY decade DESC'
        );

        $data = [];

        foreach ($fetchedData as $row)
        {
            $data[$row['decade']] = $row['decade'];
        }

        return $data;
    }
}