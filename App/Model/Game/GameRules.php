<?php

namespace App\Model\Game;

final class GameRules
{
    /** Snippet length steps (seconds) the song plays before a skip extends it. */
    public const array STEPS = [0.5, 1.0, 2.0, 5.0, 10.0, 15.0];

    public const array AVATAR_COLORS = [
        '#ff2a6d', '#5b8cff', '#39ff88', '#ffb84d', '#c842ff', '#00e5ff',
    ];

    public static function pointsForGuess(float $atSeconds): int
    {
        return max(50, (int)round(500 / ($atSeconds + 0.3)));
    }

    public static function colorForPosition(int $position): string
    {
        return self::AVATAR_COLORS[$position % count(self::AVATAR_COLORS)];
    }

    public static function initialsForName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));

        if (count($parts) >= 2)
        {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
    }
}
