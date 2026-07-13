<?php

namespace App\Model\Game;

use Apitte\Core\Exception\Api\ClientErrorException;
use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameTrack;
use App\Infrastructure\Database\Repository\GameTrackRepository;
use App\Model\Enum\GameStatusEnum;

final readonly class GameTrackAudioProvider
{
    public function __construct(
        private GameTrackRepository $gameTrackRepository,
        private string              $audioDirectory,
    )
    {
    }

    /**
     * Resolves the on-disk path for a game track's audio, but only if that exact track is
     * one the requesting viewer is currently allowed to hear — the current track while it's
     * playing, or the just-played track during its reveal. Knowing a game track id alone
     * (e.g. one leaked from another viewer's state response) is not enough on its own.
     */
    public function resolveFilePath(Game $game, int $gameTrackId): string
    {
        $gameTrack = $this->findPlayableGameTrack($game, $gameTrackId);
        $originTrack = $gameTrack->getOriginTrack();
        $fileName = $originTrack?->getAudioFilePath();

        if ($originTrack === null || !$originTrack->isAudioDownloaded() || $fileName === null)
        {
            throw new ClientErrorException('Audio not available for this track', 404);
        }

        $path = rtrim($this->audioDirectory, '/') . '/' . $fileName;

        if (!is_file($path))
        {
            throw new ClientErrorException('Audio file missing on disk', 404);
        }

        return $path;
    }

    private function findPlayableGameTrack(Game $game, int $gameTrackId): GameTrack
    {
        $tracks = $this->gameTrackRepository->findByGame($game);
        $currentTrack = $tracks[$game->getCurrentTrackPosition()] ?? null;

        if (
            $currentTrack instanceof GameTrack
            && $currentTrack->getId() === $gameTrackId
            && $game->getStatus() === GameStatusEnum::PLAYING
        )
        {
            return $currentTrack;
        }

        // Mirrors GameStateProvider's own "previous track" resolution — see the comment
        // there for why the pending-reveal position must take precedence over a plain
        // current-1 lookup once a round has ended.
        $previousPosition = $game->hasPendingReveal()
            ? $game->getPendingRevealTrackPosition()
            : $game->getCurrentTrackPosition() - 1;
        $previousTrack = $previousPosition !== null && $previousPosition >= 0 ? ($tracks[$previousPosition] ?? null) : null;

        if ($previousTrack instanceof GameTrack && $previousTrack->getId() === $gameTrackId)
        {
            return $previousTrack;
        }

        throw new ClientErrorException('Track is not available for playback right now', 403);
    }
}
