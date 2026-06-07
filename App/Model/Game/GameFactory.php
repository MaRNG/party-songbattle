<?php

namespace App\Model\Game;

use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameTrack;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Infrastructure\Database\Repository\GameRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Model\Game\Dto\GameFilterListDto;
use App\Util\TrackNameNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Nette\Utils\Random;

final readonly class GameFactory
{
    public function __construct(
        private TrackRepository        $trackRepository,
        private GameRepository         $gameRepository,
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function countMatchingTracks(GameFilterListDto $gameFilterList): int
    {
        return (int)$this->createFilteredQueryBuilder($gameFilterList)
            ->select('COUNT(DISTINCT u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function create(GameFilterListDto $gameFilterList): Game
    {
        $this->entityManager->beginTransaction();

        try {
            $game = new Game();

            $game
                ->setCode(Random::generate(4, 'a-z'))
                ->setFilters($this->createFiltersJson($gameFilterList))
                ->setHash(md5(uniqid($game->getCode(), true)));

            $this->entityManager->persist($game);
            $this->entityManager->flush();

            $this->inflateTracks($game, $gameFilterList);

            $game = $this->gameRepository->find($game->getId());

            $this->entityManager->persist($game);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $ex) {
            $this->entityManager->rollback();
            throw $ex;
        }

        return $game;
    }

    private function inflateTracks(Game $game, GameFilterListDto $gameFilterList): void
    {
        foreach (array_chunk($this->createFilteredQueryBuilder($gameFilterList)->getQuery()->getArrayResult(), 50) as $chunk)
        {
            $game = $this->gameRepository->find($game->getId());

            foreach ($chunk as $trackRow)
            {
                $track = $this->trackRepository->find($trackRow['id']);

                if ($track instanceof Track)
                {
                    $gameTrack = new GameTrack();

                    $gameTrack
                        ->setGame($game)
                        ->setOriginTrack($track);

                    $gameTrack
                        ->setTrackName($track->getName())
                        ->setArtistName(
                            implode(', ', array_map(static function (Artist $artist)
                            {
                                return $artist->getName();
                            }, $track->getArtists()->toArray()))
                        );

                    $gameTrack
                        ->setNormalizedCompleteName(
                            sprintf('%s - %s', TrackNameNormalizer::normalizeName($track->getName()), TrackNameNormalizer::normalizeName($gameTrack->getArtistName()))
                        );

                    $this->entityManager->persist($gameTrack);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            gc_collect_cycles();
        }
    }

    private function createFilteredQueryBuilder(GameFilterListDto $gameFilterList): QueryBuilder
    {
        $qb = $this->trackRepository->getBaseQuery();

        $qb
            ->select('DISTINCT u.id')
            ->innerJoin('u.artists', '_artists')
            ->innerJoin('u.genres', '_genres');

        if ($gameFilterList->year_filter !== [])
        {
            foreach ($gameFilterList->year_filter as $decade)
            {
                $qb->andWhere(sprintf('(u.release_year >= :fromYear%s AND u.release_year < :toYear%s)', $decade, $decade));

                $qb->setParameter(sprintf('fromYear%s', $decade), $decade);
                $qb->setParameter(sprintf('toYear%s', $decade), $decade + 10);
            }
        }

        if ($gameFilterList->area_filter !== [])
        {
            $qb->andWhere('_artists.area IN (:areas)');
            $qb->setParameter('areas', $gameFilterList->area_filter);
        }

        if ($gameFilterList->artist_filter !== [])
        {
            $qb->andWhere('_artists.id IN (:artistIds)');
            $qb->setParameter('artistIds', $gameFilterList->artist_filter);
        }

        if ($gameFilterList->genre_filter !== [])
        {
            $qb->andWhere('_genres.id IN (:genreIds)');
            $qb->setParameter('genreIds', $gameFilterList->genre_filter);
        }

        return $qb;
    }

    private function createFiltersJson(GameFilterListDto $gameFilterList): array
    {
        return [];
    }
}