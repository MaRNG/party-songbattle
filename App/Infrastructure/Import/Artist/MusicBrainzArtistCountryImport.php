<?php

namespace App\Infrastructure\Import\Artist;

use App\Infrastructure\Database\Entity\Tag\Tag;
use App\Infrastructure\Database\Repository\ArtistRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Infrastructure\ExternalClient\MusicBrainzExternalClient;
use App\Model\Enum\ExternalSourceEnum;
use App\Util\CliWriter;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MusicBrainzArtistCountryImport
{
    public function __construct(
        private MusicBrainzExternalClient $musicBrainzExternalClient,
        private ArtistRepository          $artistRepository,
        private EntityManagerInterface    $entityManager
    )
    {
    }

    public function import(): void
    {
        $qb = $this->artistRepository->getBaseQuery();

        $qb
            ->select('u.id, u.name')
            ->where('u.music_brainz_imported = 0');

        $countQb = clone $qb;
        $countQb->select('COUNT(1)');

        $tracksCountToImport = (int)$countQb->getQuery()->getSingleScalarResult();

        if ($tracksCountToImport > 0)
        {
            CliWriter::writeNl(sprintf('%d artists to import...', $tracksCountToImport));

            $transformedTracks = [];

            CliWriter::writeNl('Start processing artist rows...');

            $i = 1;

            foreach ($qb->getQuery()->getArrayResult() as $artistRow)
            {
                CliWriter::writeNl(sprintf('Importing artist ID %s...', $artistRow['id']));

                $this->importArtist($artistRow['id'], $artistRow['name']);

                CliWriter::writeNl(sprintf('Imported %d/%d', $i, $tracksCountToImport));

                // API RATE LIMIT

                $this->entityManager->clear();
                gc_collect_cycles();

                sleep(1);
                $i++;
            }
        } else
        {
            CliWriter::writeNl('All artists was already imported.');
        }
    }

    private function importArtist(int $artistId, string $artistName): void
    {
        $artistDto = $this->musicBrainzExternalClient->artists()->findArtist($artistName);

        $dbArtist = $this->artistRepository->find($artistId);

        if ($dbArtist === null)
        {
            return;
        }

        if ($artistDto !== null)
        {
            $dbArtist
                ->setCountry($artistDto->country ?? '-')
                ->setArea($artistDto->area ?? '-')
                ->setMusicBrainzImported(true);

            $this->entityManager->flush();

            CliWriter::writeNl(sprintf('Artist %s - country imported.', $artistName));
        } else
        {
            $dbArtist->setMusicBrainzImported(false);
            $this->entityManager->flush();
            CliWriter::writeNl(sprintf('Artist %s not found!', $artistName));
        }
    }
}