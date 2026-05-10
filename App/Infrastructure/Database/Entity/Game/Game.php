<?php

namespace App\Infrastructure\Database\Entity\Game;

use App\Infrastructure\Database\Entity\Artist\Album;
use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Model\Enum\ExternalSourceEnum;
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
}