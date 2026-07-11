<?php

namespace App\Infrastructure\Database\Entity\Game;

use App\Infrastructure\Database\Entity\Artist\Album;
use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Model\Enum\ExternalSourceEnum;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GameStatusEnum;
use App\Model\Game\GameRules;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;

#[Entity]
class Game extends BaseEntity
{
    // Invite code
    #[Column(type: 'string', nullable: false, unique: true, length: 4)]
    protected string $code;

    #[Column(type: 'string', nullable: false, unique: true, length: 32)]
    protected string $hash;

    #[Column(type: 'json', nullable: false)]
    protected array $filters;

    #[Column(type: 'string', nullable: false, enumType: GameModeEnum::class)]
    protected GameModeEnum $mode = GameModeEnum::SOLO;

    #[Column(type: 'string', nullable: false, enumType: GameStatusEnum::class)]
    protected GameStatusEnum $status = GameStatusEnum::WAITING;

    #[Column(type: 'integer', nullable: false)]
    protected int $current_track_position = 0;

    #[Column(type: 'integer', nullable: false)]
    protected int $current_step_index = 0;

    #[Column(type: 'float', nullable: false)]
    protected float $elapsed_seconds = 0;

    #[Column(type: 'float', nullable: true)]
    protected ?float $playback_resumed_at = null;

    #[Column(type: 'integer', nullable: false)]
    protected int $current_turn_position = 0;

    /** @var int[] Points awarded for a correct guess at each index of GameRules::STEPS. */
    #[Column(type: 'json', nullable: false)]
    protected array $points_per_step = GameRules::DEFAULT_POINTS_PER_STEP;

    #[Column(type: 'boolean', nullable: false)]
    protected bool $show_leaderboard_to_players = true;

    // Set whenever a round ends (correct guess, or the snippet ran out), until the
    // master dismisses it — this is the single server-side source of truth for the
    // Correct/Missed reveal screen, so every viewer (not just whoever guessed) sees the
    // same outcome, and only the master's continue action can advance past it.
    #[Column(type: 'boolean', nullable: true)]
    protected ?bool $pending_reveal_correct = null;

    #[Column(type: 'string', nullable: true, length: 40)]
    protected ?string $pending_reveal_guesser_name = null;

    #[Column(type: 'float', nullable: true)]
    protected ?float $pending_reveal_at_seconds = null;

    #[Column(type: 'integer', nullable: true)]
    protected ?int $pending_reveal_points = null;

    #[Column(type: 'integer', nullable: true)]
    protected ?int $pending_reveal_streak = null;

    #[Column(type: 'integer', nullable: true)]
    protected ?int $pending_reveal_score = null;

    // Track position the reveal is actually about. Normally that's current_track_position
    // minus 1 (advanceToNextTrack() moves the position forward the instant a round ends,
    // before the reveal is even shown) — except when that round finished the whole game,
    // where the position is deliberately left pointing at the last track instead of past
    // the end of the list. Storing it explicitly avoids re-deriving it wrong in that case.
    #[Column(type: 'integer', nullable: true)]
    protected ?int $pending_reveal_track_position = null;

    // Wall-clock timestamp the pending reveal started — only stamped for ALL mode,
    // where the round auto-advances a fixed number of seconds after the reveal
    // appears instead of waiting for the master to click "Continue".
    #[Column(type: 'float', nullable: true)]
    protected ?float $pending_reveal_started_at = null;

    #[OneToMany(targetEntity: GameTrack::class, mappedBy: 'game')]
    protected Collection $tracks;

    public function __construct()
    {
        parent::__construct();

        $this->tracks = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Game
    {
        $this->code = $code;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): Game
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @return Collection<int,GameTrack>
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): Game
    {
        $this->hash = $hash;
        return $this;
    }

    public function getMode(): GameModeEnum
    {
        return $this->mode;
    }

    public function setMode(GameModeEnum $mode): Game
    {
        $this->mode = $mode;
        return $this;
    }

    public function getStatus(): GameStatusEnum
    {
        return $this->status;
    }

    public function setStatus(GameStatusEnum $status): Game
    {
        $this->status = $status;
        return $this;
    }

    public function getCurrentTrackPosition(): int
    {
        return $this->current_track_position;
    }

    public function setCurrentTrackPosition(int $current_track_position): Game
    {
        $this->current_track_position = $current_track_position;
        return $this;
    }

    public function getCurrentStepIndex(): int
    {
        return $this->current_step_index;
    }

    public function setCurrentStepIndex(int $current_step_index): Game
    {
        $this->current_step_index = $current_step_index;
        return $this;
    }

    public function getElapsedSeconds(): float
    {
        return $this->elapsed_seconds;
    }

    public function setElapsedSeconds(float $elapsed_seconds): Game
    {
        $this->elapsed_seconds = $elapsed_seconds;
        return $this;
    }

    public function getPlaybackResumedAt(): ?float
    {
        return $this->playback_resumed_at;
    }

    public function setPlaybackResumedAt(?float $playback_resumed_at): Game
    {
        $this->playback_resumed_at = $playback_resumed_at;
        return $this;
    }

    public function getCurrentTurnPosition(): int
    {
        return $this->current_turn_position;
    }

    public function setCurrentTurnPosition(int $current_turn_position): Game
    {
        $this->current_turn_position = $current_turn_position;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getPointsPerStep(): array
    {
        return $this->points_per_step;
    }

    /**
     * @param int[] $points_per_step
     */
    public function setPointsPerStep(array $points_per_step): Game
    {
        $this->points_per_step = $points_per_step;
        return $this;
    }

    public function isShowLeaderboardToPlayers(): bool
    {
        return $this->show_leaderboard_to_players;
    }

    public function setShowLeaderboardToPlayers(bool $show_leaderboard_to_players): Game
    {
        $this->show_leaderboard_to_players = $show_leaderboard_to_players;
        return $this;
    }

    public function isPlaying(): bool
    {
        return $this->playback_resumed_at !== null;
    }

    public function hasPendingReveal(): bool
    {
        return $this->pending_reveal_correct !== null;
    }

    public function getPendingRevealCorrect(): ?bool
    {
        return $this->pending_reveal_correct;
    }

    public function getPendingRevealGuesserName(): ?string
    {
        return $this->pending_reveal_guesser_name;
    }

    public function getPendingRevealAtSeconds(): ?float
    {
        return $this->pending_reveal_at_seconds;
    }

    public function getPendingRevealPoints(): ?int
    {
        return $this->pending_reveal_points;
    }

    public function getPendingRevealStreak(): ?int
    {
        return $this->pending_reveal_streak;
    }

    public function getPendingRevealScore(): ?int
    {
        return $this->pending_reveal_score;
    }

    public function getPendingRevealTrackPosition(): ?int
    {
        return $this->pending_reveal_track_position;
    }

    public function getPendingRevealStartedAt(): ?float
    {
        return $this->pending_reveal_started_at;
    }

    public function setPendingRevealStartedAt(?float $pending_reveal_started_at): Game
    {
        $this->pending_reveal_started_at = $pending_reveal_started_at;
        return $this;
    }

    public function setPendingReveal(
        bool    $correct,
        int     $trackPosition,
        ?string $guesserName = null,
        ?float  $atSeconds = null,
        ?int    $points = null,
        ?int    $streak = null,
        ?int    $score = null,
    ): Game
    {
        $this->pending_reveal_correct = $correct;
        $this->pending_reveal_track_position = $trackPosition;
        $this->pending_reveal_guesser_name = $guesserName;
        $this->pending_reveal_at_seconds = $atSeconds;
        $this->pending_reveal_points = $points;
        $this->pending_reveal_streak = $streak;
        $this->pending_reveal_score = $score;

        return $this;
    }

    public function clearPendingReveal(): Game
    {
        $this->pending_reveal_correct = null;
        $this->pending_reveal_track_position = null;
        $this->pending_reveal_guesser_name = null;
        $this->pending_reveal_at_seconds = null;
        $this->pending_reveal_points = null;
        $this->pending_reveal_streak = null;
        $this->pending_reveal_score = null;
        $this->pending_reveal_started_at = null;

        return $this;
    }

    /**
     * Elapsed time within the current step, accounting for live playback.
     */
    public function getCurrentElapsedSeconds(): float
    {
        if ($this->playback_resumed_at === null)
        {
            return $this->elapsed_seconds;
        }

        return $this->elapsed_seconds + (microtime(true) - $this->playback_resumed_at);
    }
}