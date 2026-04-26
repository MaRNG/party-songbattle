<?php

namespace App\UI\Forms;

use App\Infrastructure\Database\Repository\ArtistRepository;
use App\Infrastructure\Database\Repository\TrackRepository;
use Nette\Application\UI\Form;

final readonly class GameFormFactory
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private TrackRepository $trackRepository,
    )
    {
    }

    public function createNewGameForm(): Form
    {
        $form = new Form();

        $form->addCheckboxList('year_filter', 'Filtrovat rok', ['__all__' => 'Vše'] + $this->trackRepository->getDecadesFilterData());
        $form->addCheckboxList('artist_filter', 'Filtrovat interprety', ['__all__' => 'Vše'] + $this->artistRepository->getSelectFormData());

        $form->addSubmit('create', 'Založit');

        return $form;
    }
}