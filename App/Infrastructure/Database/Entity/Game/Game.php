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
    #[Column(type: 'string', nullable: false)]
    protected array $code;

    #[Column(type: 'json', nullable: false)]
    protected array $filters;

    #[OneToMany(targetEntity: GameTrack::class, mappedBy: 'game')]
    protected Collection $tracks;

    public function __construct()
    {
        parent::__construct();

        $this->tracks = new ArrayCollection();
    }

    public function getCode(): array
    {
        return $this->code;
    }

    public function setCode(array $code): Game
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
}