<?php

namespace App\Model\Game;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GamePlayer;
use App\Infrastructure\Database\Entity\Game\GameTrack;
use App\Infrastructure\Database\Repository\GamePlayerRepository;
use App\Infrastructure\Database\Repository\GameTrackRepository;
use App\Model\Enum\ExternalSourceEnum;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GamePlayerRoleEnum;
use App\Model\Enum\GameStatusEnum;
use App\Model\Game\Dto\GamePlayerStateDto;
use App\Model\Game\Dto\GameRoundResultDto;
use App\Model\Game\Dto\GameStateDto;
use App\Model\Game\Dto\GameTrackInfoDto;

final readonly class GameStateProvider
{
    public function __construct(
        private GamePlayerRepository $gamePlayerRepository,
        private GameTrackRepository  $gameTrackRepository,
        private GameSessionManager   $gameSessionManager,
    )
    {
    }

    public function get(Game $game, GamePlayer $viewer): GameStateDto
    {
        $tracks = $this->gameTrackRepository->findByGame($game);
        $currentTrack = $tracks[$game->getCurrentTrackPosition()] ?? null;

        $isMaster = $viewer->getRole() === GamePlayerRoleEnum::MASTER;

        $currentTurnPlayer = $game->getMode() === GameModeEnum::ROBIN
            ? $this->gameSessionManager->getCurrentTurnPlayer($game)
            : null;

        $revealTrack = $currentTrack !== null
            && $isMaster
            && $game->getMode() !== GameModeEnum::SOLO;

        // Tracks are already inflated the instant the game is created, long before the
        // master presses "Start game" — gate this on status too, or the master's browser
        // would try to load/play the first track's audio while still sitting in the lobby.
        $spotifyTrackId = ($isMaster && $currentTrack !== null && $game->getStatus() === GameStatusEnum::PLAYING)
            ? $this->resolveSpotifyTrackId($currentTrack)
            : null;

        $stepSeconds = GameRules::STEPS[$game->getCurrentStepIndex()];
        $elapsedSeconds = min($game->getCurrentElapsedSeconds(), $stepSeconds);

        // The previously played track's round has already concluded (correctly guessed,
        // or skipped/advanced past) — unlike the current track, there's no spoiler risk
        // in revealing it, and unlike `track` this is shown to every viewer regardless of
        // role or mode, so solo players and non-master players in robin/all both find out
        // what the song was.
        $previousPosition = $game->getCurrentTrackPosition() - 1;
        $previousGameTrack = $previousPosition >= 0 ? ($tracks[$previousPosition] ?? null) : null;
        $previousTrack = $previousGameTrack instanceof GameTrack
            ? new GameTrackInfoDto(
                $previousGameTrack->getTrackName(),
                $previousGameTrack->getArtistName(),
                // Only the master's browser holds a live Spotify Connect device, so only
                // they get the id needed to play the full track back on the reveal screen.
                $isMaster ? $this->resolveSpotifyTrackId($previousGameTrack) : null,
            )
            : null;

        $roundResult = $game->hasPendingReveal()
            ? new GameRoundResultDto(
                correct    : (bool)$game->getPendingRevealCorrect(),
                guesserName: $game->getPendingRevealGuesserName(),
                atSeconds  : $game->getPendingRevealAtSeconds(),
                points     : $game->getPendingRevealPoints(),
                streak     : $game->getPendingRevealStreak(),
                score      : $game->getPendingRevealScore(),
            )
            : null;

        return new GameStateDto(
            code         : $game->getCode(),
            hash         : $game->getHash(),
            mode         : $game->getMode(),
            status       : $game->getStatus(),
            viewerRole   : $viewer->getRole(),
            isPlaying    : $game->isPlaying(),
            elapsedSeconds: $elapsedSeconds,
            stepSeconds  : $stepSeconds,
            stepIndex    : $game->getCurrentStepIndex(),
            totalSteps   : count(GameRules::STEPS),
            trackPosition: $game->getCurrentTrackPosition(),
            totalTracks  : count($tracks),
            track        : $revealTrack ? new GameTrackInfoDto($currentTrack->getTrackName(), $currentTrack->getArtistName()) : null,
            previousTrack: $previousTrack,
            spotifyTrackId: $spotifyTrackId,
            roundResult  : $roundResult,
            showLeaderboardToPlayers: $game->isShowLeaderboardToPlayers(),
            players      : array_map(
                fn(GamePlayer $player) => $this->mapPlayer($player, $viewer, $currentTurnPlayer),
                $this->gamePlayerRepository->findByGame($game)
            ),
        );
    }

    private function resolveSpotifyTrackId(GameTrack $currentTrack): ?string
    {
        $originTrack = $currentTrack->getOriginTrack();

        return $originTrack->getExternalSource() === ExternalSourceEnum::SPOTIFY
            ? $originTrack->getExternalId()
            : null;
    }

    private function mapPlayer(GamePlayer $player, GamePlayer $viewer, ?GamePlayer $currentTurnPlayer): GamePlayerStateDto
    {
        return new GamePlayerStateDto(
            id           : $player->getId(),
            name         : $player->getName(),
            initials     : $player->getInitials(),
            color        : $player->getColor(),
            role         : $player->getRole(),
            score        : $player->getScore(),
            streak       : $player->getStreak(),
            guesses      : $player->getGuesses(),
            connected    : $player->isConnected(),
            isViewer     : $player->getId() === $viewer->getId(),
            isCurrentTurn: $currentTurnPlayer instanceof GamePlayer && $currentTurnPlayer->getId() === $player->getId(),
        );
    }
}
