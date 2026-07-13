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
    /**
     * Browsers yt-dlp's --cookies-from-browser knows how to read cookies from.
     * A value may also carry a ":profile" or "+keyring" suffix, e.g. "firefox:default-release".
     */
    private const SUPPORTED_COOKIE_BROWSERS = [
        'brave', 'chrome', 'chromium', 'edge', 'firefox', 'opera', 'safari', 'vivaldi', 'whale',
    ];

    public function __construct(
        private readonly TrackRepository               $trackRepository,
        private readonly EntityManagerInterface        $entityManager,
        private readonly TrackAudioDownloaderInterface $trackAudioDownloader,
        private readonly string                        $targetDirectory,
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

        $this->addOption(
            'cookies-from-browser',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf(
                'Read cookies from this browser\'s local profile for yt-dlp (--cookies-from-browser), ' .
                'used to get past YouTube age/bot-check gates. One of: %s. Optionally suffixed with ' .
                ':PROFILE (e.g. "firefox:default-release"). Works on Linux as long as the named browser ' .
                'and its profile are present on this machine.',
                implode(', ', self::SUPPORTED_COOKIE_BROWSERS)
            )
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
        $limit = $limitOption !== null ? (int)$limitOption : null;

        $cookiesFromBrowser = $input->getOption('cookies-from-browser');

        if ($cookiesFromBrowser !== null)
        {
            $browserName = strtolower(strtok($cookiesFromBrowser, ':+'));

            if (!in_array($browserName, self::SUPPORTED_COOKIE_BROWSERS, true))
            {
                CliWriter::writeNl(
                    sprintf(
                        'Unknown browser "%s" for --cookies-from-browser. Supported: %s.',
                        $browserName,
                        implode(', ', self::SUPPORTED_COOKIE_BROWSERS)
                    )
                );

                return self::FAILURE;
            }
        }

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

            $artistNames = implode(
                ', ', array_map(
                static fn($artist) => $artist->getName(),
                $track->getArtists()->toArray()
            )
            );

            CliWriter::writeNl(
                sprintf(
                    '[#%d] Processing track #%d: %s - %s',
                    $total,
                    $track->getId(),
                    $artistNames,
                    $track->getName()
                )
            );

            try
            {
                $result = $this->trackAudioDownloader->download($track, $this->targetDirectory, $cookiesFromBrowser);

                if ($result === null)
                {
                    CliWriter::writeNl(sprintf('[#%d] No audio source found, skipping.', $total));
                    $skipped++;
                    continue;
                }

                // Stored as a bare filename, not the absolute path yt-dlp wrote to — the
                // serving side resolves it against its own configured audio directory, so
                // the DB never pins down (or leaks) an absolute filesystem path.
                $track->setAudioYoutubeUrl($result->youtubeUrl);
                $track->setAudioFilePath(basename($result->filePath));
                $track->setAudioDownloaded(true);

                $this->entityManager->persist($track);
                $this->entityManager->flush();
                $this->entityManager->detach($track);

                CliWriter::writeNl(sprintf('[#%d] Downloaded and saved to "%s".', $total, $result->filePath));
                $downloaded++;
            } catch (\Throwable $e)
            {
                CliWriter::writeNl(sprintf('[#%d] Failed: %s', $total, $e->getMessage()));
                $failed++;
            }
        }

        CliWriter::writeNl(
            sprintf(
                'Done. Processed: %d, downloaded: %d, skipped: %d, failed: %d.',
                $total,
                $downloaded,
                $skipped,
                $failed
            )
        );

        return self::SUCCESS;
    }
}
