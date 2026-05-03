<?php

namespace App\UI\Modules\Front\Search;

use App\Infrastructure\Database\Repository\TrackRepository;
use App\UI\Modules\Front\BaseFrontPresenter;

final class SearchPresenter extends BaseFrontPresenter
{
    #[\Nette\DI\Attributes\Inject]
    public TrackRepository $trackRepository;

    public function renderDefault(): void
    {
    }

    public function handleSearch(?string $query = null): void
    {
        if (empty(trim($query)))
        {
            $this->sendJson([]);
        }

        $tracks = $this->trackRepository->searchTracks($query, 15);
        $results = [];

        foreach ($tracks as $track)
        {
            $artists = [];
            foreach ($track->getArtists() as $artist)
            {
                $artists[] = $artist->getName();
            }
            $artistString = implode(', ', $artists);

            $genres = [];
            foreach ($track->getGenres() as $genre)
            {
                $genres[] = $genre->getName();
            }

            $tags = [];
            foreach ($track->getTags() as $tag)
            {
                $tags[] = $tag->getName();
            }

            $results[] = [
                'id' => $track->getId(),
                'text' => $track->getName() . ' - ' . $artistString,
                'details' => [
                    'name' => $track->getName(),
                    'artists' => $artistString,
                    'duration_ms' => $track->getDurationMs(),
                    'release_year' => $track->getReleaseYear(),
                    'popularity_score' => $track->getPopularityScore(),
                    'genres' => implode(', ', $genres),
                    'tags' => implode(', ', $tags),
                ]
            ];
        }

        $this->sendJson($results);
    }
}
