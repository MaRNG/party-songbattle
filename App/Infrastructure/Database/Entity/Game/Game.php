<?php

namespace App\Infrastructure\Database\Entity\Game;

use App\Infrastructure\Database\Entity\Artist\Album;
use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Model\Enum\ExternalSourceEnum;
use App\Model\Enum\GameModeEnum;
use App\Model\Enum\GameStatusEnum;
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

    #[Column(type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $playback_resumed_at = null;

    #[Column(type: 'integer', nullable: false)]
    protected int $current_turn_position = 0;

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

    public function getPlaybackResumedAt(): ?\DateTimeInterface
    {
        return $this->playback_resumed_at;
    }

    public function setPlaybackResumedAt(?\DateTimeInterface $playback_resumed_at): Game
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

    public function isPlaying(): bool
    {
        return $this->playback_resumed_at !== null;
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

        return $this->elapsed_seconds + (time() - $this->playback_resumed_at->getTimestamp());
    }
}