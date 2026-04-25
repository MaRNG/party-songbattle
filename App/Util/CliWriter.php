<?php

namespace App\Util;

use Nette\StaticClass;

final readonly class CliWriter
{
    use StaticClass;

    public static function writeNl(string $text, bool $includeTime = true): void
    {
        if (php_sapi_name() === 'cli')
        {
            if ($includeTime)
            {
                echo sprintf('[%s] %s' . PHP_EOL, date(\DateTimeInterface::ATOM), $text);
            }
            else
            {
                echo $text . PHP_EOL;
            }
        }
    }
}