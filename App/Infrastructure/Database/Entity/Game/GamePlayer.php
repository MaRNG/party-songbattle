<?php

namespace App\Infrastructure\Database\Entity\Game;

use App\Infrastructure\Database\Entity\BaseEntity;
use App\Model\Enum\GamePlayerRoleEnum;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
#[Index(name: 'game_player_token_idx', columns: ['token'])]
class GamePlayer extends BaseEntity
{
    #[ManyToOne(targetEntity: Game::class)]
    #[JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false)]
    protected ?Game $game = null;

    #[Column(type: 'string', nullable: false, unique: true, length: 64)]
    protected string $token;

    #[Column(type: 'string', nullable: false, length: 40)]
    protected string $name;

    #[Column(type: 'string', nullable: false, length: 4)]
    protected string $initials;

    #[Column(type: 'string', nullable: false, length: 16)]
    protected string $color;

    #[Column(type: 'string', nullable: false, enumType: GamePlayerRoleEnum::class)]
    protected GamePlayerRoleEnum $role;

    #[Column(type: 'integer', nullable: false)]
    protected int $score = 0;

    #[Column(type: 'integer', nullable: false)]
    protected int $streak = 0;

    #[Column(type: 'integer', nullable: false)]
    protected int $guesses = 0;

    #[Column(type: 'boolean', nullable: false)]
    protected bool $connected = true;

    #[Column(type: 'datetime', nullable: false)]
    protected \DateTimeInterface $last_seen;

    public function __construct()
    {
        parent::__construct();

        $this->last_seen = new \DateTime();
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): GamePlayer
    {
        $this->game = $game;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): GamePlayer
    {
        $this->token = $token;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): GamePlayer
    {
        $this->name = $name;
        return $this;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): GamePlayer
    {
        $this->initials = $initials;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): GamePlayer
    {
        $this->color = $color;
        return $this;
    }

    public function getRole(): GamePlayerRoleEnum
    {
        return $this->role;
    }

    public function setRole(GamePlayerRoleEnum $role): GamePlayer
    {
        $this->role = $role;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): GamePlayer
    {
        $this->score = $score;
        return $this;
    }

    public function getStreak(): int
    {
        return $this->streak;
    }

    public function setStreak(int $streak): GamePlayer
    {
        $this->streak = $streak;
        return $this;
    }

    public function getGuesses(): int
    {
        return $this->guesses;
    }

    public function setGuesses(int $guesses): GamePlayer
    {
        $this->guesses = $guesses;
        return $this;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $connected): GamePlayer
    {
        $this->connected = $connected;
        return $this;
    }

    public function getLastSeen(): \DateTimeInterface
    {
        return $this->last_seen;
    }

    public function setLastSeen(\DateTimeInterface $last_seen): GamePlayer
    {
        $this->last_seen = $last_seen;
        return $this;
    }
}
