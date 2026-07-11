<?php

namespace App\Model\Game;

final class GameRules
{
    /** Snippet length steps (seconds) the song plays before a skip extends it. */
    public const array STEPS = [0.5, 1.0, 2.0, 5.0, 10.0, 15.0];

    /** Default points awarded for a correct guess at each index of STEPS, master-configurable per game. */
    public const array DEFAULT_POINTS_PER_STEP = [500, 300, 200, 100, 75, 50];

    public const array AVATAR_COLORS = [
        '#ff2a6d', '#5b8cff', '#39ff88', '#ffb84d', '#c842ff', '#00e5ff',
    ];

    /** ALL mode: guess attempts a player gets per song before they're locked out of the round. */
    public const int ALL_MODE_MAX_ATTEMPTS = 3;

    /** ALL mode: seconds the Correct/Missed reveal stays up before auto-advancing to the next song. */
    public const float ALL_MODE_REVEAL_SECONDS = 10.0;

    /**
     * @param int[] $pointsPerStep
     */
    public static function pointsForStep(array $pointsPerStep, int $stepIndex): int
    {
        return $pointsPerStep[$stepIndex] ?? self::DEFAULT_POINTS_PER_STEP[$stepIndex] ?? 50;
    }

    /**
     * ALL mode reuses the same master-configurable point tiers as points-per-step, just
     * keyed by finishing placement (0 = first correct guess) instead of snippet step.
     *
     * @param int[] $pointsPerStep
     */
    public static function pointsForPlacement(array $pointsPerStep, int $placementIndex): int
    {
        return self::pointsForStep($pointsPerStep, $placementIndex);
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
