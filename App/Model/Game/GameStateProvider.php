<?php

namespace App\Model\Game;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GamePlayer;
use App\Infrastructure\Database\Repository\GamePlayerRepository;
use App\Infrastructure\Database\Repository\GameTrackRepository;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GamePlayerRoleEnum;
use App\Model\Game\Dto\GamePlayerStateDto;
use App\Model\Game\Dto\GameStateDto;
use App\Model\Game\Dto\GameTrackInfoDto;

final readonly class GameStateProvider
{
    public function __construct(
        private GamePlayerRepository $gamePlayerRepository,
        private GameTrackRepository  $gameTrackRepository,
    )
    {
    }

    public function get(Game $game, GamePlayer $viewer): GameStateDto
    {
        $tracks = $this->gameTrackRepository->findByGame($game);
        $currentTrack = $tracks[$game->getCurrentTrackPosition()] ?? null;

        $revealTrack = $currentTrack !== null
            && $viewer->getRole() === GamePlayerRoleEnum::MASTER
            && $game->getMode() !== GameModeEnum::SOLO;

        return new GameStateDto(
            code         : $game->getCode(),
            hash         : $game->getHash(),
            mode         : $game->getMode(),
            status       : $game->getStatus(),
            viewerRole   : $viewer->getRole(),
            isPlaying    : $game->isPlaying(),
            elapsedSeconds: $game->getCurrentElapsedSeconds(),
            stepSeconds  : GameRules::STEPS[$game->getCurrentStepIndex()],
            stepIndex    : $game->getCurrentStepIndex(),
            totalSteps   : count(GameRules::STEPS),
            trackPosition: $game->getCurrentTrackPosition(),
            totalTracks  : count($tracks),
            track        : $revealTrack ? new GameTrackInfoDto($currentTrack->getTrackName(), $currentTrack->getArtistName()) : null,
            players      : array_map(
                fn(GamePlayer $player) => $this->mapPlayer($player, $viewer),
                $this->gamePlayerRepository->findByGame($game)
            ),
        );
    }

    private function mapPlayer(GamePlayer $player, GamePlayer $viewer): GamePlayerStateDto
    {
        return new GamePlayerStateDto(
            id       : $player->getId(),
            name     : $player->getName(),
            initials : $player->getInitials(),
            color    : $player->getColor(),
            role     : $player->getRole(),
            score    : $player->getScore(),
            streak   : $player->getStreak(),
            guesses  : $player->getGuesses(),
            connected: $player->isConnected(),
            isViewer : $player->getId() === $viewer->getId(),
        );
    }
}
