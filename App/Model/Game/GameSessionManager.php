<?php

namespace App\Model\Game;

use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GameGuess;
use App\Infrastructure\Database\Entity\Game\GamePlayer;
use App\Infrastructure\Database\Entity\Game\GameTrack;
use App\Infrastructure\Database\Repository\GamePlayerRepository;
use App\Infrastructure\Database\Repository\GameTrackRepository;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GamePlayerRoleEnum;
use App\Model\Enum\GameStatusEnum;
use App\Model\Game\Dto\GameGuessResultDto;
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

    public function setPlaying(Game $game, bool $playing): Game
    {
        if ($playing && !$game->isPlaying())
        {
            $game->setPlaybackResumedAt(new \DateTime());
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
        $wasPlaying = $game->isPlaying();

        if ($game->getCurrentStepIndex() >= count(GameRules::STEPS) - 1)
        {
            return $this->advanceToNextTrack($game);
        }

        $game
            ->setCurrentStepIndex($game->getCurrentStepIndex() + 1)
            ->setElapsedSeconds(0.0)
            ->setPlaybackResumedAt($wasPlaying ? new \DateTime() : null);

        $this->entityManager->flush();

        return $game;
    }

    public function nextSong(Game $game): Game
    {
        return $this->advanceToNextTrack($game);
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

            $game
                ->setElapsedSeconds($atSeconds)
                ->setPlaybackResumedAt(null);

            $track->setPlayed(true);

            $gameGuess = new GameGuess();

            $gameGuess
                ->setGame($game)
                ->setGameTrack($track)
                ->setPlayer($player)
                ->setCorrect(true)
                ->setAtSeconds($atSeconds)
                ->setPoints($points);

            $this->entityManager->persist($gameGuess);
            $this->entityManager->flush();

            return new GameGuessResultDto(
                correct  : true,
                atSeconds: $atSeconds,
                points   : $points,
                score    : $player->getScore(),
                streak   : $player->getStreak(),
            );
        }

        $player->setStreak(0);

        $this->entityManager->flush();

        return new GameGuessResultDto(
            correct  : false,
            atSeconds: $atSeconds,
            points   : 0,
            score    : $player->getScore(),
            streak   : 0,
        );
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
