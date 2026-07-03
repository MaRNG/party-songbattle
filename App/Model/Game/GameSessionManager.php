<?php

namespace App\Model\Game;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameGuess;
use App\Infrastructure\Database\Entity\Game\GamePlayer;
use App\Infrastructure\Database\Entity\Game\GameTrack;
use App\Infrastructure\Database\Repository\GamePlayerRepository;
use App\Infrastructure\Database\Repository\GameTrackRepository;
use App\Model\Enum\ExternalSourceEnum;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GamePlayerRoleEnum;
use App\Model\Enum\GameStatusEnum;
use App\Model\Game\Dto\GameGuessResultDto;
use App\Model\Game\Dto\GameTrackInfoDto;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Utils\Random;
use Nette\Utils\Strings;

final readonly class GameSessionManager
{
    public function __construct(
        private GamePlayerRepository   $gamePlayerRepository,
        private GameTrackRepository    $gameTrackRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function configureMode(Game $game, GameModeEnum $mode): Game
    {
        $game->setMode($mode);

        $this->entityManager->flush();

        return $game;
    }

    public function join(Game $game, string $name): GamePlayer
    {
        $position = $this->gamePlayerRepository->countByGame($game);

        $role = ($game->getMode() === GameModeEnum::SOLO || $position === 0)
            ? GamePlayerRoleEnum::MASTER
            : GamePlayerRoleEnum::PLAYER;

        $player = new GamePlayer();

        $player
            ->setGame($game)
            ->setToken(Random::generate(48))
            ->setName($name)
            ->setInitials(GameRules::initialsForName($name))
            ->setColor(GameRules::colorForPosition($position))
            ->setRole($role);

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $player;
    }

    public function start(Game $game): Game
    {
        $game
            ->setStatus(GameStatusEnum::PLAYING)
            ->setCurrentTrackPosition(0)
            ->setCurrentStepIndex(0)
            ->setCurrentTurnPosition(0)
            ->setElapsedSeconds(0.0)
            ->setPlaybackResumedAt(null);

        $this->entityManager->flush();

        return $game;
    }

    public function autoPauseIfExpired(Game $game): Game
    {
        if (!$game->isPlaying())
        {
            return $game;
        }

        $stepLimit = GameRules::STEPS[$game->getCurrentStepIndex()];

        if ($game->getCurrentElapsedSeconds() < $stepLimit)
        {
            return $game;
        }

        $game
            ->setElapsedSeconds($stepLimit)
            ->setPlaybackResumedAt(null);

        $this->entityManager->flush();

        return $game;
    }

    public function setPlaying(Game $game, bool $playing): Game
    {
        if ($playing && !$game->isPlaying())
        {
            $game->setPlaybackResumedAt(microtime(true));
        }
        elseif (!$playing && $game->isPlaying())
        {
            $game
                ->setElapsedSeconds($game->getCurrentElapsedSeconds())
                ->setPlaybackResumedAt(null);
        }

        $this->entityManager->flush();

        return $game;
    }

    public function skip(Game $game): Game
    {
        if ($game->getCurrentStepIndex() >= count(GameRules::STEPS) - 1)
        {
            return $this->advanceToNextTrack($game);
        }

        $this->advanceStep($game);

        return $game;
    }

    public function nextSong(Game $game): Game
    {
        return $this->advanceToNextTrack($game);
    }

    public function restart(Game $game): Game
    {
        $game
            ->setElapsedSeconds(0.0)
            ->setPlaybackResumedAt(microtime(true));

        $this->entityManager->flush();

        return $game;
    }

    public function submitGuess(Game $game, GamePlayer $player, string $guess): GameGuessResultDto
    {
        $track = $this->gameTrackRepository->findAtPosition($game, $game->getCurrentTrackPosition());
        $atSeconds = $game->getCurrentElapsedSeconds();
        $correct = $track instanceof GameTrack && $this->matchesGuess($track, $guess);

        $player->setGuesses($player->getGuesses() + 1);

        if ($correct)
        {
            $points = GameRules::pointsForGuess($atSeconds);

            $player
                ->setScore($player->getScore() + $points)
                ->setStreak($player->getStreak() + 1);

            $gameGuess = new GameGuess();

            $gameGuess
                ->setGame($game)
                ->setGameTrack($track)
                ->setPlayer($player)
                ->setCorrect(true)
                ->setAtSeconds($atSeconds)
                ->setPoints($points);

            $this->entityManager->persist($gameGuess);

            // A correct guess ends the round for this track — move on to the next one
            // (or finish the game) instead of just pausing in place, so guessing right
            // is what actually advances the game.
            $this->advanceToNextTrack($game);

            return new GameGuessResultDto(
                correct  : true,
                roundOver: true,
                atSeconds: $atSeconds,
                points   : $points,
                score    : $player->getScore(),
                streak   : $player->getStreak(),
                track    : $this->buildTrackInfo($track, $player),
            );
        }

        $player->setStreak(0);

        // A wrong guess on the last step means the snippet limit is exhausted — there's
        // no further step to reveal more of the track, so treat it the same as a skip
        // past the last step: end the round instead of leaving it open to guess forever.
        $isLastStep = $game->getCurrentStepIndex() >= count(GameRules::STEPS) - 1;

        if ($isLastStep)
        {
            $this->advanceToNextTrack($game);
        }
        else
        {
            // A wrong guess costs the player their attempt at this step — automatically
            // advance to the next (longer) step, same as a manual skip, instead of
            // leaving the same short snippet open to guess forever.
            $this->advanceStep($game);
        }

        return new GameGuessResultDto(
            correct  : false,
            roundOver: $isLastStep,
            atSeconds: $atSeconds,
            points   : 0,
            score    : $player->getScore(),
            streak   : 0,
            track    : $isLastStep && $track instanceof GameTrack ? $this->buildTrackInfo($track, $player) : null,
        );
    }

    private function advanceStep(Game $game): void
    {
        $wasPlaying = $game->isPlaying();

        $game
            ->setCurrentStepIndex($game->getCurrentStepIndex() + 1)
            ->setElapsedSeconds(0.0)
            ->setPlaybackResumedAt($wasPlaying ? microtime(true) : null);

        $this->entityManager->flush();
    }

    private function buildTrackInfo(GameTrack $track, GamePlayer $player): GameTrackInfoDto
    {
        $originTrack = $track->getOriginTrack();

        // Only the master's browser holds a live Spotify Connect device, so only they
        // get the id needed to play the full track back on the reveal screen.
        $spotifyTrackId = ($player->getRole() === GamePlayerRoleEnum::MASTER && $originTrack->getExternalSource() === ExternalSourceEnum::SPOTIFY)
            ? $originTrack->getExternalId()
            : null;

        return new GameTrackInfoDto($track->getTrackName(), $track->getArtistName(), $spotifyTrackId);
    }

    private function advanceToNextTrack(Game $game): Game
    {
        $currentTrack = $this->gameTrackRepository->findAtPosition($game, $game->getCurrentTrackPosition());

        if ($currentTrack instanceof GameTrack)
        {
            $currentTrack->setPlayed(true);
        }

        $nextPosition = $game->getCurrentTrackPosition() + 1;

        if ($nextPosition >= $this->gameTrackRepository->countByGame($game))
        {
            $game
                ->setStatus(GameStatusEnum::FINISHED)
                ->setPlaybackResumedAt(null);

            $this->entityManager->flush();

            return $game;
        }

        $game
            ->setCurrentTrackPosition($nextPosition)
            ->setCurrentStepIndex(0)
            ->setElapsedSeconds(0.0)
            ->setPlaybackResumedAt(null)
            ->setCurrentTurnPosition($game->getCurrentTurnPosition() + 1);

        $this->entityManager->flush();

        return $game;
    }

    private function matchesGuess(GameTrack $track, string $guess): bool
    {
        $needle = Strings::lower(trim($guess));

        if ($needle === '')
        {
            return false;
        }

        return Strings::contains(Strings::lower($track->getTrackName()), $needle)
            || Strings::contains(Strings::lower($track->getArtistName()), $needle);
    }
}
