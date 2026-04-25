<?php

namespace App\Infrastructure\Import\Playlist;

use App\Infrastructure\Database\Entity\Album\Album;
use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Infrastructure\Database\Repository\AlbumRepository;
use App\Infrastructure\Database\Repository\ArtistRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Infrastructure\ExternalClient\Dto\SpotifyAlbumDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyArtistDto;
use App\Infrastructure\ExternalClient\Dto\SpotifyTrackDto;
use App\Infrastructure\ExternalClient\SpotifyExternalClient;
use App\Model\Enum\ExternalSourceEnum;
use App\Util\CliWriter;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SpotifyPlaylistImport
{
    public function __construct(
        private SpotifyExternalClient  $spotifyExternalClient,
        private EntityManagerInterface $entityManager,
        private TrackRepository        $trackRepository,
        private ArtistRepository       $artistRepository,
        private AlbumRepository        $albumRepository,
    )
    {
    }

    public function importPlaylistTracks(string $playlistId): void
    {
        CliWriter::writeNl('List Spotify tracks...');

        $playlistDto = $this->spotifyExternalClient->playlists()->listTracks($playlistId);
        $i = 0;

        foreach ($playlistDto->items as $spotifyTrackDto)
        {
            $this->importTrack($spotifyTrackDto);

            $i++;

            if ($i % 10 === 0)
            {
                $this->entityManager->clear();
                gc_collect_cycles();
            }
        }

        CliWriter::writeNl('Playlist imported');
    }

    private function importTrack(SpotifyTrackDto $spotifyTrackDto): Track
    {
        CliWriter::writeNl(sprintf('Importing track %s (#%s)...', $spotifyTrackDto->name, $spotifyTrackDto->spotifyId));

        $track = $this->trackRepository->findOneBy([
           'external_id' => $spotifyTrackDto->spotifyId,
           'external_source' => ExternalSourceEnum::SPOTIFY->value
        ]);

        if ($track === null)
        {
            $track = new Track();

            $track
                ->setExternalId($spotifyTrackDto->spotifyId)
                ->setExternalSource(ExternalSourceEnum::SPOTIFY);
        }

        $track
            ->setName($spotifyTrackDto->name)
            ->setDurationMs($spotifyTrackDto->durationMs)
            ->setReleaseDate($spotifyTrackDto->releaseDate)
            ->setReleaseYear($spotifyTrackDto->releaseYear);

        $existAlbum = null;

        foreach ($track->getAlbums() as $album)
        {
            if ($album->getExternalId() === $spotifyTrackDto->album->spotifyId)
            {
                $existAlbum = $album;
            }
        }

        if ($existAlbum === null)
        {
            $track->getAlbums()->add($this->getOrCreateAlbum($spotifyTrackDto->album));
        }

        foreach ($spotifyTrackDto->artists as $spotifyArtistDto)
        {
            $existArtist = null;

            foreach ($track->getArtists() as $artist)
            {
                if ($artist->getExternalId() === $spotifyTrackDto->spotifyId)
                {
                    $existArtist = $artist;
                }
            }

            if ($existArtist === null)
            {
                $track->getArtists()->add($this->getOrCreateArtist($spotifyArtistDto));
            }
        }

        $this->entityManager->persist($track);
        $this->entityManager->flush();

        CliWriter::writeNl('Track imported!');

        return $track;
    }

    private function getOrCreateArtist(SpotifyArtistDto $spotifyArtistDto): Artist
    {
        CliWriter::writeNl(sprintf('Importing artist %s (#%s)', $spotifyArtistDto->name, $spotifyArtistDto->spotifyId));

        $artist = $this->artistRepository->findOneBy([
            'external_id' => $spotifyArtistDto->spotifyId,
            'external_source' => ExternalSourceEnum::SPOTIFY->value
        ]);

        if ($artist === null)
        {
            $artist = new Artist();

            $artist
                ->setExternalId($spotifyArtistDto->spotifyId)
                ->setExternalSource(ExternalSourceEnum::SPOTIFY);
        }

        $artist->setName($spotifyArtistDto->name);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $artist;
    }

    private function getOrCreateAlbum(SpotifyAlbumDto $spotifyAlbumDto): Album
    {
        CliWriter::writeNl(sprintf('Importing album %s (#%s)', $spotifyAlbumDto->name, $spotifyAlbumDto->spotifyId));

        $album = $this->albumRepository->findOneBy([
            'external_id' => $spotifyAlbumDto->spotifyId,
            'external_source' => ExternalSourceEnum::SPOTIFY->value
        ]);

        if ($album === null)
        {
            $album = new Album();

            $album
                ->setExternalId($spotifyAlbumDto->spotifyId)
                ->setExternalSource(ExternalSourceEnum::SPOTIFY);
        }

        $album->setName($spotifyAlbumDto->name);

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $album;
    }
}