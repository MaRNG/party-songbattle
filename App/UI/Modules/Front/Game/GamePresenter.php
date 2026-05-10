<?php

namespace App\UI\Modules\Front\Game;

use App\Infrastructure\Database\Repository\GameRepository;
use App\UI\Modules\Front\BaseFrontPresenter;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

final class GamePresenter extends BaseFrontPresenter
{
    #[\Nette\DI\Attributes\Inject]
    public GameRepository $gameRepository;

    public function renderSingleplayer(string $gameHash): void
    {
        $this->template->game = $game = $this->gameRepository->findByHash($gameHash);

        if ($game === null)
        {
            throw new BadRequestException('Game not found!', IResponse::S404_NotFound);
        }
    }

    public function renderTracksPool(string $gameHash): void
    {
        $this->template->game = $game = $this->gameRepository->findByHash($gameHash);

        if ($game === null)
        {
            throw new BadRequestException('Game not found!', IResponse::S404_NotFound);
        }
    }
}