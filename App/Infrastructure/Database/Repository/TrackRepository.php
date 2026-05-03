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

    /**
     * @return Track[]
     */
    public function searchTracks(string $query, int $limit = 10): array
    {
        $qb = $this->getBaseQuery();
        
        $qb->select('u')
           ->leftJoin('u.artists', 'a')
           ->addSelect('a');

        $qb->where('u.name LIKE :query OR a.name LIKE :query')
           ->setParameter('query', '%' . $query . '%');

        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}