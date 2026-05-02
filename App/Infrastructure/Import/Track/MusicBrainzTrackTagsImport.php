<?php

namespace App\Infrastructure\Import\Track;

use App\Infrastructure\Database\Repository\TrackRepository;
use App\Infrastructure\ExternalClient\MusicBrainzExternalClient;
use App\Util\CliWriter;
use Doctrine\ORM\EntityManagerInterface;
use App\Infrastructure\Database\Entity\Tag\Tag;
use App\Model\Enum\ExternalSourceEnum;

final readonly class MusicBrainzTrackTagsImport
{
    public function __construct(
        private MusicBrainzExternalClient $musicBrainzExternalClient,
        private TrackRepository           $trackRepository,
        private EntityManagerInterface    $entityManager
    )
    {
    }

    public function import(): void
    {
        $qb = $this->trackRepository->getBaseQuery();

        $qb
            ->select('u.id, u.name, artists.name AS artistName')
            ->leftJoin('u.artists', 'artists')
            ->where('u.music_brainz_imported = 0');

        $countQb = clone $qb;
        $countQb->select('COUNT(1)');

        $tracksCountToImport = (int)$countQb->getQuery()->getSingleScalarResult();

        if ($tracksCountToImport > 0)
        {
            CliWriter::writeNl(sprintf('%d tracks to import...', $tracksCountToImport));

            $transformedTracks = [];

            CliWriter::writeNl('Transforming track rows...');

            foreach ($qb->getQuery()->getArrayResult() as $trackRow)
            {
                if (isset($transformedTracks[$trackRow['id']]))
                {
                    $transformedTracks[$trackRow['id']]['artists'][] = [
                        'name' => $trackRow['artistName'],
                    ];
                } else
                {
                    $transformedTracks[$trackRow['id']] = [
                        'id' => $trackRow['id'],
                        'name' => $trackRow['name'],
                        'artists' => [
                            ['name' => $trackRow['artistName']],
                        ],
                    ];
                }
            }

            CliWriter::writeNl('Start processing track rows...');

            $i = 1;

            foreach ($transformedTracks as $transformedTrack)
            {
                CliWriter::writeNl(sprintf('Importing track ID %s...', $transformedTrack['id']));

                $this->importTrack($transformedTrack['id'], $transformedTrack['name'], array_map(static function (array $artistRow)
                {
                    return $artistRow['name'];
                }, $transformedTrack['artists']));

                CliWriter::writeNl(sprintf('Imported %d/%d', $i, $tracksCountToImport));

                // API RATE LIMIT

                $this->entityManager->clear();
                gc_collect_cycles();

                sleep(1);
                $i++;
            }
        } else
        {
            CliWriter::writeNl('All tracks was already imported.');
        }
    }

    private function importTrack(int $trackId, string $trackName, array $artistNames): void
    {
        $trackDto = $this->musicBrainzExternalClient->tracks()->findTrack($trackName, $artistNames);

        $dbTrack = $this->trackRepository->find($trackId);

        if ($dbTrack === null)
        {
            return;
        }

        if ($trackDto !== null)
        {
            foreach ($trackDto->tags as $tagName)
            {
                $tagName = trim($tagName);
                if (empty($tagName))
                {
                    continue;
                }

                $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['name' => $tagName]);

                if ($tag === null)
                {
                    $tag = new Tag();
                    $tag->setName($tagName);
                    $tag->setExternalSource(ExternalSourceEnum::MUSICBRAINZ);
                    $this->entityManager->persist($tag);
                }

                if ($dbTrack->getTags()->contains($tag) === false)
                {
                    $dbTrack->getTags()->add($tag);
                }
            }

            $dbTrack->setMusicBrainzImported(true);
            $this->entityManager->flush();

            CliWriter::writeNl(sprintf('Track %s [%s] - tags imported.', $trackName, implode(', ', $artistNames)));
        } else
        {
            $dbTrack->setMusicBrainzImported(false);
            $this->entityManager->flush();
            CliWriter::writeNl(sprintf('Track %s [%s] not found!', $trackName, implode(', ', $artistNames)));
        }
    }
}