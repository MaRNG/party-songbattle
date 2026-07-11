<?php

namespace App\Model\Track\Audio;

use App\Infrastructure\Database\Entity\Track\Track;

interface TrackAudioDownloaderInterface
{
    /**
     * Resolves and downloads audio for the given track into $targetDirectory.
     * Returns null when no suitable source was found for the track.
     *
     * @throws \Throwable on download failure
     */
    public function download(Track $track, string $targetDirectory): ?TrackAudioDownloadResult;
}
