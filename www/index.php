<?php
declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/api/'))
{
    App\Bootstrap::runApi();
}
else
{
    App\Bootstrap::runWeb();
}