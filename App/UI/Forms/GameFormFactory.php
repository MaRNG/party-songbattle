<?php

namespace App\UI\Forms;

use App\Infrastructure\Database\Repository\ArtistRepository;
use App\Infrastructure\Database\Repository\GenreRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use Nette\Application\UI\Form;

final readonly class GameFormFactory
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private TrackRepository  $trackRepository,
        private GenreRepository  $genreRepository,
    )
    {
    }

    public function createNewGameForm(): Form
    {
        $form = new Form();

        $form->addCheckboxList('year_filter', 'Filtrovat rok', ['__all__' => 'Vše'] + $this->trackRepository->getDecadesFilterData());
        $form->addCheckboxList('genre_filter', 'Filtrovat žánry', ['__all__' => 'Vše'] + $this->genreRepository->getSelectFormData());
        $form->addCheckboxList('area_filter', 'Filtrovat oblasti', ['__all__' => 'Vše'] + $this->trackRepository->getAreaFilterData());

        $form->addSubmit('create', 'Založit');

        return $form;
    }
}