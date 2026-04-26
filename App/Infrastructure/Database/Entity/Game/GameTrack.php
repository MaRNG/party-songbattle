<?php

namespace App\Infrastructure\Database\Entity\Game;

use App\Infrastructure\Database\Entity\Artist\Album;
use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Track\Track;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[Entity]
#[Index(name: 'track_name_idx', columns: ['track_name'])]
#[Index(name: 'artist_name_idx', columns: ['artist_name'])]
#[Index(name: 'normalized_complete_name_idx', columns: ['normalized_complete_name'])]
class GameTrack extends BaseEntity
{
    #[ManyToOne(targetEntity: Track::class)]
    #[JoinColumn(name: 'origin_track_id', referencedColumnName: 'id', nullable: false)]
    protected ?Track $origin_track = null;

    #[ManyToOne(targetEntity: Game::class, inversedBy: 'tracks')]
    #[JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false)]
    protected ?Game $game = null;

    #[Column(type: 'string', nullable: false)]
    protected string $track_name;

    #[Column(type: 'string', nullable: false)]
    protected string $artist_name;

    #[Column(type: 'string', nullable: false)]
    protected string $normalized_complete_name;

    public function getOriginTrack(): Track|null
    {
        return $this->origin_track;
    }

    public function setOriginTrack(?Track $origin_track): GameTrack
    {
        $this->origin_track = $origin_track;
        return $this;
    }

    public function getGame(): Game|null
    {
        return $this->game;
    }

    public function setGame(?Game $game): GameTrack
    {
        $this->game = $game;
        return $this;
    }

    public function getTrackName(): string
    {
        return $this->track_name;
    }

    public function setTrackName(string $track_name): GameTrack
    {
        $this->track_name = $track_name;
        return $this;
    }

    public function getArtistName(): string
    {
        return $this->artist_name;
    }

    public function setArtistName(string $artist_name): GameTrack
    {
        $this->artist_name = $artist_name;
        return $this;
    }

    public function getNormalizedCompleteName(): string
    {
        return $this->normalized_complete_name;
    }

    public function setNormalizedCompleteName(string $normalized_complete_name): GameTrack
    {
        $this->normalized_complete_name = $normalized_complete_name;
        return $this;
    }
}