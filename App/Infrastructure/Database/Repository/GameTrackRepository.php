<?php

namespace App\Infrastructure\Database\Repository;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameTrack;

final class GameTrackRepository extends BaseRepository
{
    protected static function getEntityClass(): string
    {
        return GameTrack::class;
    }

    /**
     * @return array<int,GameTrack>
     */
    public function findByGame(Game $game): array
    {
        return $this->findBy(['game' => $game], ['id' => 'ASC']);
    }

    public function findAtPosition(Game $game, int $position): ?GameTrack
    {
        return $this->findByGame($game)[$position] ?? null;
    }

    public function countByGame(Game $game): int
    {
        return count($this->findByGame($game));
    }

    /**
     * @return array<int,GameTrack>
     */
    public function searchByGame(Game $game, string $query, int $limit = 8): array
    {
        $qb = $this->getBaseQuery();

        $qb->andWhere('u.game = :game')
            ->setParameter('game', $game);

        // Parentheses are required here — `andWhere()` does not wrap a raw OR-string in
        // parens, and `AND` binds tighter than `OR` in DQL, so without them this would
        // match `u.game = :game AND u.track_name LIKE :query OR u.artist_name LIKE :query`
        // (i.e. any game's tracks by artist name), not the intended `:game AND (name OR artist)`.
        $qb->andWhere('(u.track_name LIKE :query OR u.artist_name LIKE :query)')
            ->setParameter('query', '%' . $query . '%');

        $qb->orderBy('u.track_name', 'ASC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
