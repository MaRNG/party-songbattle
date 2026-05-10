<?php

namespace App\UI\Forms;

use App\Infrastructure\Database\Repository\ArtistRepository;
use App\Infrastructure\Database\Repository\GenreRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Model\Game\Dto\GameFilterListDto;
use App\Model\Game\GameFactory;
use Nette\Application\UI\Form;

final readonly class GameFormFactory
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private TrackRepository  $trackRepository,
        private GenreRepository  $genreRepository,
        private GameFactory      $gameTracksFactory
    )
    {
    }

    public function createNewGameForm(): Form
    {
        $form = new Form();

        $form->addCheckboxList('year_filter', 'Filtrovat rok', ['__all__' => 'Vše'] + $this->trackRepository->getDecadesFilterData());
        $form->addCheckboxList('genre_filter', 'Filtrovat žánry', ['__all__' => 'Vše'] + $this->genreRepository->getSelectFormData());
        $form->addCheckboxList('area_filter', 'Filtrovat oblasti', ['__all__' => 'Vše'] + $this->trackRepository->getAreaFilterData());
        $form->addCheckboxList('artist_filter', 'Filtrovat oblasti', ['__all__' => 'Vše'] + $this->artistRepository->getSelectFormData());

        $form->addSubmit('create', 'Založit');

        $form->onSuccess[] = function (Form $form): void
        {
            $gameFilterList = $form->getValues(GameFilterListDto::class);

            $createdGame = $this->gameTracksFactory->create($gameFilterList);

            $form->getPresenterIfExists()->redirect('Game:singleplayer', ['gameHash' => $createdGame->getHash()]);
        };

        return $form;
    }
}