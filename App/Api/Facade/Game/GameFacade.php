<?php

namespace App\Api\Facade\Game;

use Apitte\Core\Exception\Api\ClientErrorException;
use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GamePlayer;
use App\Infrastructure\Database\Repository\GamePlayerRepository;
use App\Infrastructure\Database\Repository\GameRepository;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GamePlayerRoleEnum;
use App\Model\Game\Dto\GameFilterListDto;
use App\Model\Game\Dto\GameFilterOptionsDto;
use App\Model\Game\Dto\GameGuessResultDto;
use App\Model\Game\Dto\GameSessionDto;
use App\Model\Game\Dto\GameStateDto;
use App\Model\Game\GameFactory;
use App\Model\Game\GameFilterOptionsProvider;
use App\Model\Game\GameSessionManager;
use App\Model\Game\GameStateProvider;

final readonly class GameFacade
{
    public function __construct(
        private GameRepository           $gameRepository,
        private GamePlayerRepository     $gamePlayerRepository,
        private GameFactory              $gameFactory,
        private GameFilterOptionsProvider $gameFilterOptionsProvider,
        private GameSessionManager       $gameSessionManager,
        private GameStateProvider        $gameStateProvider,
    )
    {
    }

    public function getFilterOptions(GameFilterListDto $filters): GameFilterOptionsDto
    {
        return $this->gameFilterOptionsProvider->get($filters);
    }

    public function create(GameFilterListDto $filters, GameModeEnum $mode, string $playerName): GameSessionDto
    {
        $game = $this->gameFactory->create($filters);

        $this->gameSessionManager->configureMode($game, $mode);
        $player = $this->gameSessionManager->join($game, $playerName);

        return new GameSessionDto($game, $player);
    }

    public function join(string $hash, string $playerName): GameSessionDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->gameSessionManager->join($game, $playerName);

        return new GameSessionDto($game, $player);
    }

    public function getState(string $hash, string $token): GameStateDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->getPlayerByToken($game, $token);

        return $this->gameStateProvider->get($game, $player);
    }

    public function start(string $hash, string $token): GameStateDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->getPlayerByToken($game, $token);

        $this->assertMaster($player);
        $this->gameSessionManager->start($game);

        return $this->gameStateProvider->get($game, $player);
    }

    public function setPlaying(string $hash, string $token, bool $playing): GameStateDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->getPlayerByToken($game, $token);

        $this->assertMaster($player);
        $this->gameSessionManager->setPlaying($game, $playing);

        return $this->gameStateProvider->get($game, $player);
    }

    public function skip(string $hash, string $token): GameStateDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->getPlayerByToken($game, $token);

        $this->assertMaster($player);
        $this->gameSessionManager->skip($game);

        return $this->gameStateProvider->get($game, $player);
    }

    public function nextSong(string $hash, string $token): GameStateDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->getPlayerByToken($game, $token);

        $this->assertMaster($player);
        $this->gameSessionManager->nextSong($game);

        return $this->gameStateProvider->get($game, $player);
    }

    public function submitGuess(string $hash, string $token, string $guess): GameGuessResultDto
    {
        $game = $this->getGameByHash($hash);
        $player = $this->getPlayerByToken($game, $token);

        return $this->gameSessionManager->submitGuess($game, $player, $guess);
    }

    private function getGameByHash(string $hash): Game
    {
        $game = $this->gameRepository->findByHash($hash);

        if (!$game instanceof Game)
        {
            throw new ClientErrorException('Game not found', 404);
        }

        return $game;
    }

    private function getPlayerByToken(Game $game, string $token): GamePlayer
    {
        $player = $this->gamePlayerRepository->findByToken($token);

        if (!$player instanceof GamePlayer || $player->getGame()?->getId() !== $game->getId())
        {
            throw new ClientErrorException('Player not found', 404);
        }

        return $player;
    }

    private function assertMaster(GamePlayer $player): void
    {
        if ($player->getRole() !== GamePlayerRoleEnum::MASTER)
        {
            throw new ClientErrorException('Only the game master can perform this action', 403);
        }
    }
}
