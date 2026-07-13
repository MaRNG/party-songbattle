<?php

namespace App\Model\Track\Audio;

use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Util\CliWriter;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Component\Process\Process;

final class YouTubeTrackAudioDownloader implements TrackAudioDownloaderInterface
{
    private const SEARCH_RESULTS_COUNT = 10;
    private const MIN_TITLE_SIMILARITY = 15.0;
    private const MIN_DURATION_SECONDS = 30;
    private const MAX_DURATION_SECONDS = 900;
    private const DOWNLOAD_ATTEMPTS = 3;

    /** Title fragments that signal a clean, video-free audio source. */
    private const PREFERRED_TITLE_HINTS = [
        'official audio' => 25.0,
        'lyric video' => 20.0,
        'lyrics' => 20.0,
        'audio' => 8.0,
    ];

    /** Title fragments that signal the source is the music video itself. */
    private const VIDEO_TITLE_HINTS = [
        'official music video',
        'official video',
        'music video',
    ];

    /** Title fragments that signal an undesired alternate version, unless the track itself is that version. */
    private const UNDESIRED_VERSION_HINTS = [
        'live', 'cover', 'remix', 'reaction', 'sped up', 'slowed',
        'nightcore', '8d audio', 'karaoke', 'instrumental', 'mashup', 'type beat',
    ];

    public function download(Track $track, string $targetDirectory, ?string $cookiesFromBrowser = null): ?TrackAudioDownloadResult
    {
        $trackName = $track->getName();
        $artistsName = implode(', ', array_map(static function(Artist $artist)
        {
            return $artist->getName();
        }, $track->getArtists()->toArray()));

        $trackFindName = "{$trackName} - {$artistsName}";

        CliWriter::writeNl(sprintf('Finding track: %s', $trackFindName));

        $candidates = $this->search($trackFindName, $cookiesFromBrowser);

        $rated = [];

        foreach ($candidates as $candidate)
        {
            $score = $this->rateCandidate($candidate, $track, $trackName, $artistsName);

            if ($score === null)
            {
                continue;
            }

            $rated[] = ['entry' => $candidate, 'score' => $score];
        }

        if ($rated === [])
        {
            CliWriter::writeNl('No suitable YouTube result found.');
            return null;
        }

        usort($rated, static fn(array $a, array $b) => $b['score'] <=> $a['score']);

        foreach ($rated as $candidate)
        {
            CliWriter::writeNl(
                sprintf(
                    '  candidate: score=%.1f channel="%s" title="%s"',
                    $candidate['score'],
                    $candidate['entry']['channel'] ?? $candidate['entry']['uploader'] ?? '?',
                    $candidate['entry']['title'] ?? '?'
                )
            );
        }

        $best = $rated[0]['entry'];
        $videoId = $best['id'];
        $videoUrl = $best['webpage_url'] ?? "https://www.youtube.com/watch?v={$videoId}";

        CliWriter::writeNl(sprintf('Selected: %s (score %.1f)', $videoUrl, $rated[0]['score']));

        $filePath = $this->downloadAudio($videoUrl, $track, $targetDirectory, $cookiesFromBrowser);

        if ($filePath === null)
        {
            return null;
        }

        return new TrackAudioDownloadResult($videoUrl, $filePath);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function search(string $trackFindName, ?string $cookiesFromBrowser): array
    {
        $arguments = ['yt-dlp'];

        if ($cookiesFromBrowser !== null)
        {
            $arguments[] = '--cookies-from-browser';
            $arguments[] = $cookiesFromBrowser;
        }

        $arguments[] = '-J';
        $arguments[] = 'ytsearch' . self::SEARCH_RESULTS_COUNT . ':' . $trackFindName;

        $process = new Process($arguments);

        $process->setTimeout(120);
        $process->mustRun();

        $decodedData = Json::decode($process->getOutput(), forceArrays: true);

        return array_values(array_filter($decodedData['entries'] ?? [], static fn($entry) => $entry !== null));
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function rateCandidate(array $entry, Track $track, string $trackName, string $artistsName): ?float
    {
        $title = (string)($entry['title'] ?? '');

        if ($title === '')
        {
            return null;
        }

        if (($entry['live_status'] ?? null) === 'is_live')
        {
            return null;
        }

        $duration = isset($entry['duration']) ? (int)$entry['duration'] : null;

        if ($duration !== null && ($duration < self::MIN_DURATION_SECONDS || $duration > self::MAX_DURATION_SECONDS))
        {
            return null;
        }

        $similarity = $this->similarity($trackName . ' ' . $artistsName, $title);

        if ($similarity < self::MIN_TITLE_SIMILARITY)
        {
            return null;
        }

        $score = $similarity;

        $titleLower = Strings::lower($title);
        $channel = (string)($entry['channel'] ?? $entry['uploader'] ?? '');
        $channelLower = trim(Strings::lower($channel));

        // Auto-generated "Artist - Topic" channels contain the clean track audio only.
        if (preg_match('/-\s*topic$/', $channelLower) === 1)
        {
            $score += 50.0;
        }

        foreach (self::PREFERRED_TITLE_HINTS as $hint => $bonus)
        {
            if (str_contains($titleLower, $hint))
            {
                $score += $bonus;
                break;
            }
        }

        foreach (self::VIDEO_TITLE_HINTS as $hint)
        {
            if (str_contains($titleLower, $hint))
            {
                $score -= 15.0;
                break;
            }
        }

        $trackNameLower = Strings::lower($trackName);

        foreach (self::UNDESIRED_VERSION_HINTS as $hint)
        {
            if (str_contains($titleLower, $hint) && !str_contains($trackNameLower, $hint))
            {
                $score -= 30.0;
            }
        }

        $expectedDurationSeconds = (int)round($track->getDurationMs() / 1000);

        if ($duration !== null && $expectedDurationSeconds > 0)
        {
            $score += max(-40.0, 25.0 - abs($duration - $expectedDurationSeconds));
        }

        $viewCount = (int)($entry['view_count'] ?? 0);
        $score += min(10.0, log10($viewCount + 1));

        return $score;
    }

    private function similarity(string $a, string $b): float
    {
        similar_text($this->normalize($a), $this->normalize($b), $percent);

        return $percent;
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Strings::lower(Strings::toAscii($text))));
    }

    private function downloadAudio(string $videoUrl, Track $track, string $targetDirectory, ?string $cookiesFromBrowser): ?string
    {
        $outputTemplate = rtrim($targetDirectory, '/') . '/track_' . $track->getId() . '.%(ext)s';
        $filePath = rtrim($targetDirectory, '/') . '/track_' . $track->getId() . '.mp3';

        for ($attempt = 1; $attempt <= self::DOWNLOAD_ATTEMPTS; $attempt++)
        {
            CliWriter::writeNl(sprintf('Downloading (attempt %d/%d): %s', $attempt, self::DOWNLOAD_ATTEMPTS, $videoUrl));

            try
            {
                $arguments = ['yt-dlp'];

                if ($cookiesFromBrowser !== null)
                {
                    $arguments[] = '--cookies-from-browser';
                    $arguments[] = $cookiesFromBrowser;
                }

                array_push(
                    $arguments,
                    '-f', 'bestaudio/best',
                    '--extract-audio',
                    '--audio-format', 'mp3',
                    '--audio-quality', '0',
                    '--no-playlist',
                    '--sleep-interval', '1',
                    '--max-sleep-interval', '3',
                    '-o', $outputTemplate,
                    $videoUrl,
                );

                $process = new Process($arguments);

                $process->setTimeout(300);
                $process->mustRun();

                if (is_file($filePath))
                {
                    return $filePath;
                }

                CliWriter::writeNl(sprintf('Attempt %d/%d finished but expected file "%s" was not found.', $attempt, self::DOWNLOAD_ATTEMPTS, $filePath));
            }
            catch (\Throwable $e)
            {
                CliWriter::writeNl(sprintf('Attempt %d/%d failed: %s', $attempt, self::DOWNLOAD_ATTEMPTS, $e->getMessage()));
            }
        }

        CliWriter::writeNl(sprintf('Giving up after %d failed download attempts.', self::DOWNLOAD_ATTEMPTS));

        return null;
    }
}
