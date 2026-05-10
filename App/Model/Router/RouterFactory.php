<?php

namespace App\Model\Router;

use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    private RouteList $router;

    public function __construct()
    {
        $this->router = new RouteList();
    }

    public function create(): RouteList
    {
        $this->buildFront();

        return $this->router;
    }

    protected function buildFront(): void
    {
        $this->router[] = $list = new RouteList('Front');

        // Specific route
        $list->addRoute('/hledat', 'Search:default');
        $list->addRoute('/statistiky', 'Statistics:default');

        $list->addRoute('/hra/singleplayer/<gameHash>', 'Game:singleplayer');
        $list->addRoute('/hra/tracks-pool/<gameHash>', 'Game:tracksPool');

        // Default route
        $list->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');
    }
}