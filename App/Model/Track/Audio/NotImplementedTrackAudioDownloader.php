<?php

namespace App\Model\Track\Audio;

use App\Infrastructure\Database\Entity\Track\Track;

/**
 * Placeholder implementation wired in services.neon by default.
 * Replace the service registration with a real implementation of
 * TrackAudioDownloaderInterface once the download logic is ready.
 */
final class NotImplementedTrackAudioDownloader implements TrackAudioDownloaderInterface
{
    public function download(Track $track, string $targetDirectory): ?TrackAudioDownloadResult
    {
        throw new \RuntimeException(
            'TrackAudioDownloaderInterface has no real implementation registered yet. ' .
            'Implement it and swap NotImplementedTrackAudioDownloader in config/app/services.neon.'
        );
    }
}
