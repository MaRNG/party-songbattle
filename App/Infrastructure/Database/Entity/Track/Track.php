<?php

namespace App\Infrastructure\Database\Entity\Track;

use App\Infrastructure\Database\Entity\Artist\Artist;
use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Genre\Genre;
use App\Infrastructure\Database\Entity\Playlist\Playlist;
use App\Model\Enum\ExternalSourceEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class Track extends BaseEntity
{
    #[Column(unique: true, nullable: true)]
    protected ?string $external_id = null;

    #[Column(nullable: true, enumType: ExternalSourceEnum::class)]
    protected ?string $external_source = null;

    #[Column(type: "smallint", nullable: true)]
    protected ?int $external_popularity_score = null;

    #[Column(nullable: false)]
    protected string $name;

    #[Column(type: "smallint", nullable: true)]
    protected ?int $release_year = null;

    #[Column(nullable: true)]
    protected ?\DateTime $release_date = null;

    #[Column(type: "smallint", nullable: true)]
    protected ?int $popularity_score = null;

    #[Column(nullable: false)]
    protected int $duration_ms;

    #[ManyToMany(targetEntity: Playlist::class, inversedBy: "tracks")]
    #[JoinTable(name: "tracks_playlists")]
    protected Collection $playlists;

    #[ManyToMany(targetEntity: Artist::class, inversedBy: "tracks")]
    #[JoinTable(name: "tracks_artists")]
    protected Collection $artists;

    #[ManyToMany(targetEntity: Genre::class, inversedBy: "tracks")]
    #[JoinTable(name: "tracks_genres")]
    protected Collection $genres;

    public function __construct()
    {
        parent::__construct();

        $this->playlists = new ArrayCollection();
        $this->artists = new ArrayCollection();
        $this->genres = new ArrayCollection();
    }

    public function getExternalId(): string|null
    {
        return $this->external_id;
    }

    public function setExternalId(?string $external_id): Track
    {
        $this->external_id = $external_id;
        return $this;
    }

    public function getExternalSource(): string|null
    {
        return $this->external_source;
    }

    public function setExternalSource(?string $external_source): Track
    {
        $this->external_source = $external_source;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Track
    {
        $this->name = $name;
        return $this;
    }

    public function getReleaseYear(): int|null
    {
        return $this->release_year;
    }

    public function setReleaseYear(?int $release_year): Track
    {
        $this->release_year = $release_year;
        return $this;
    }

    public function getReleaseDate(): \DateTime|null
    {
        return $this->release_date;
    }

    public function setReleaseDate(?\DateTime $release_date): Track
    {
        $this->release_date = $release_date;
        return $this;
    }

    public function getExternalPopularityScore(): int|null
    {
        return $this->external_popularity_score;
    }

    public function setExternalPopularityScore(?int $external_popularity_score): Track
    {
        $this->external_popularity_score = $external_popularity_score;
        return $this;
    }

    public function getPopularityScore(): int|null
    {
        return $this->popularity_score;
    }

    public function setPopularityScore(?int $popularity_score): Track
    {
        $this->popularity_score = $popularity_score;
        return $this;
    }

    public function getDurationMs(): int
    {
        return $this->duration_ms;
    }

    public function setDurationMs(int $duration_ms): Track
    {
        $this->duration_ms = $duration_ms;
        return $this;
    }

    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function getArtists(): Collection
    {
        return $this->artists;
    }

    public function getGenres(): Collection
    {
        return $this->genres;
    }
}