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
        $list->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');
    }
}