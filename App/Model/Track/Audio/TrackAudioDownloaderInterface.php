<?php

namespace App\Model\Track\Audio;

use App\Infrastructure\Database\Entity\Track\Track;

interface TrackAudioDownloaderInterface
{
    /**
     * Resolves and downloads audio for the given track into $targetDirectory.
     * Returns null when no suitable source was found for the track.
     *
     * $cookiesFromBrowser is passed through to yt-dlp's --cookies-from-browser
     * (e.g. "chrome", "firefox", "firefox:default-release") to get past
     * age/bot-check gates that otherwise block search or download.
     *
     * @throws \Throwable on download failure
     */
    public function download(Track $track, string $targetDirectory, ?string $cookiesFromBrowser = null): ?TrackAudioDownloadResult;
}
