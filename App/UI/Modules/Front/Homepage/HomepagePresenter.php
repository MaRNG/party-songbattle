<?php

namespace App\UI\Modules\Front\Homepage;

use App\UI\Forms\GameFormFactory;
use App\UI\Modules\Front\BaseFrontPresenter;
use Nette\Application\UI\Form;

final class HomepagePresenter extends BaseFrontPresenter
{
    #[\Nette\DI\Attributes\Inject]
    public GameFormFactory $gameFormFactory;

    public function renderDefault(): void
    {

    }

    public function createComponentNewGameForm(): Form
    {
        $form = $this->gameFormFactory->createNewGameForm();

        return $form;
    }
}