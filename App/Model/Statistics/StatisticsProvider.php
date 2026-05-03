<?php

namespace App\Model\Statistics;

use App\Infrastructure\Database\Repository\ArtistRepository;
use App\Infrastructure\Database\Repository\GenreRepository;
use App\Infrastructure\Database\Repository\TagRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Model\Statistics\Dto\StatisticsDto;

final readonly class StatisticsProvider
{
    public function __construct(
        private TrackRepository  $trackRepository,
        private ArtistRepository $artistRepository,
        private GenreRepository  $genreRepository,
        private TagRepository    $tagRepository
    )
    {
    }

    public function get(): StatisticsDto
    {
        return new StatisticsDto(
            trackCounts        : $this->getTracksCount(),
            trackCountByDecades: $this->getTracksCountByDecades(),
            trackCountByArtists: $this->getTracksCountByArtists(),
            trackCountByAreas  : $this->getTracksCountByAreas(),
            trackCountByGenres : $this->getTracksCountByGenres(),
            trackCountByTags   : $this->getTracksCountByTags()
        );
    }

    private function getTracksCount(): int
    {
        return (int)$this->trackRepository->getBaseQuery()->select('COUNT(1)')->getQuery()->getSingleScalarResult();
    }

    private function getTracksCountByDecades(): array
    {
        /**
         * SELECT
         * FLOOR(release_year / 10) * 10 AS decade,
         * COUNT(*) AS count
         * FROM track
         * GROUP BY decade
         * ORDER BY decade
         */

        $tracksCountByDecades = [];
        $rows = $this->trackRepository->getBaseQuery()->select('u.release_year, COUNT(1) AS count')
            ->orderBy('u.release_year', 'DESC')
            ->groupBy('u.release_year')
            ->getQuery()->getArrayResult();

        foreach ($rows as $row)
        {
            if (isset($tracksCountByDecades[(floor($row['release_year'] / 10) * 10)]) === false)
            {
                $tracksCountByDecades[(floor($row['release_year'] / 10) * 10)] = $row['count'];
            } else
            {
                $tracksCountByDecades[(floor($row['release_year'] / 10) * 10)] += $row['count'];
            }
        }

        return $tracksCountByDecades;
    }

    private function getTracksCountByArtists(): array
    {
        $rows = $this->artistRepository->getBaseQuery()
            ->select('u.name, COUNT(1) AS trackCount')
            ->innerJoin('u.tracks', '_tracks')
            ->orderBy('trackCount', 'DESC')
            ->groupBy('u.id')
            ->getQuery()->getArrayResult();

        $tracksCountByArtists = [];

        foreach ($rows as $row)
        {
            if (isset($tracksCountByArtists[$row['name']]) === false)
            {
                $tracksCountByArtists[$row['name']] = $row['trackCount'];
            } else
            {
                $tracksCountByArtists[$row['name']] += $row['trackCount'];
            }
        }

        return $tracksCountByArtists;
    }

    private function getTracksCountByAreas(): array
    {
        $rows = $this->artistRepository->getBaseQuery()
            ->select('u.area, COUNT(1) AS trackCount')
            ->innerJoin('u.tracks', '_tracks')
            ->where('u.area != \'\' AND u.area != \'-\'')
            ->orderBy('trackCount', 'DESC')
            ->groupBy('u.area')
            ->getQuery()->getArrayResult();

        $tracksCountByAreas = [];

        foreach ($rows as $row)
        {
            if (isset($tracksCountByAreas[$row['area']]) === false)
            {
                $tracksCountByAreas[$row['area']] = $row['trackCount'];
            } else
            {
                $tracksCountByAreas[$row['area']] += $row['trackCount'];
            }
        }

        return $tracksCountByAreas;
    }

    private function getTracksCountByTags(): array
    {
        $rows = $this->tagRepository->getBaseQuery()
            ->select('u.name, COUNT(1) AS trackCount')
            ->innerJoin('u.tracks', '_tracks')
            ->orderBy('trackCount', 'DESC')
            ->groupBy('u.name')
            ->getQuery()->getArrayResult();

        $tracksCountByTags = [];

        foreach ($rows as $row)
        {
            if (isset($tracksCountByTags[$row['name']]) === false)
            {
                $tracksCountByTags[$row['name']] = $row['trackCount'];
            } else
            {
                $tracksCountByTags[$row['name']] += $row['trackCount'];
            }
        }

        return $tracksCountByTags;
    }

    private function getTracksCountByGenres(): array
    {
        $rows = $this->genreRepository->getBaseQuery()
            ->select('u.name, COUNT(1) AS trackCount')
            ->innerJoin('u.tracks', '_tracks')
            ->orderBy('trackCount', 'DESC')
            ->groupBy('u.name')
            ->getQuery()->getArrayResult();

        $tracksCountByGenres = [];

        foreach ($rows as $row)
        {
            if (isset($tracksCountByGenres[$row['name']]) === false)
            {
                $tracksCountByGenres[$row['name']] = $row['trackCount'];
            } else
            {
                $tracksCountByGenres[$row['name']] += $row['trackCount'];
            }
        }

        return $tracksCountByGenres;
    }
}