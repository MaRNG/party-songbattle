<?php

namespace App\UI\Modules\Front\Statistics;

use App\Model\Statistics\StatisticsProvider;
use App\UI\Forms\GameFormFactory;
use App\UI\Modules\Front\BaseFrontPresenter;
use Nette\Application\UI\Form;

final class StatisticsPresenter extends BaseFrontPresenter
{
    #[\Nette\DI\Attributes\Inject]
    public StatisticsProvider $statisticsProvider;

    public function renderDefault(): void
    {
        $this->getTemplate()->statistics = $this->statisticsProvider->get();
    }
}