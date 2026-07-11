<?php

namespace App\Commands\Track;

use App\Infrastructure\Database\Entity\Track\Track;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Model\Track\Audio\TrackAudioDownloaderInterface;
use App\Util\CliWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'tracks:download-audio')]
final class DownloadTrackAudioCommand extends Command
{
    public function __construct(
        private readonly TrackRepository               $trackRepository,
        private readonly EntityManagerInterface         $entityManager,
        private readonly TrackAudioDownloaderInterface  $trackAudioDownloader,
        private readonly string                         $targetDirectory,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum number of tracks to process in this run'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_dir($this->targetDirectory) && !mkdir($this->targetDirectory, 0775, true) && !is_dir($this->targetDirectory))
        {
            CliWriter::writeNl(sprintf('Could not create target directory "%s".', $this->targetDirectory));
            return self::FAILURE;
        }

        $limitOption = $input->getOption('limit');
        $limit = $limitOption !== null ? (int) $limitOption : null;

        CliWriter::writeNl('Start downloading track audio...');

        $total = 0;
        $downloaded = 0;
        $skipped = 0;
        $failed = 0;

        /** @var Track $track */
        foreach ($this->trackRepository->iterateTracksWithoutDownloadedAudio() as $track)
        {
            if ($limit !== null && $total >= $limit)
            {
                break;
            }

            $total++;

            $artistNames = implode(', ', array_map(
                static fn ($artist) => $artist->getName(),
                $track->getArtists()->toArray()
            ));

            CliWriter::writeNl(sprintf(
                '[#%d] Processing track #%d: %s - %s',
                $total,
                $track->getId(),
                $artistNames,
                $track->getName()
            ));

            try
            {
                $result = $this->trackAudioDownloader->download($track, $this->targetDirectory);

                if ($result === null)
                {
                    CliWriter::writeNl(sprintf('[#%d] No audio source found, skipping.', $total));
                    $skipped++;
                    continue;
                }

                $track->setAudioYoutubeUrl($result->youtubeUrl);
                $track->setAudioFilePath($result->filePath);
                $track->setAudioDownloaded(true);

                $this->entityManager->persist($track);
                $this->entityManager->flush();
                $this->entityManager->detach($track);

                CliWriter::writeNl(sprintf('[#%d] Downloaded and saved to "%s".', $total, $result->filePath));
                $downloaded++;
            }
            catch (\Throwable $e)
            {
                CliWriter::writeNl(sprintf('[#%d] Failed: %s', $total, $e->getMessage()));
                $failed++;
            }
        }

        CliWriter::writeNl(sprintf(
            'Done. Processed: %d, downloaded: %d, skipped: %d, failed: %d.',
            $total,
            $downloaded,
            $skipped,
            $failed
        ));

        return self::SUCCESS;
    }
}
