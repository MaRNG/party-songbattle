<?php

namespace App\Model\Track\Audio;

final readonly class TrackAudioDownloadResult
{
    public function __construct(
        public string $youtubeUrl,
        public string $filePath,
    )
    {
    }
}
