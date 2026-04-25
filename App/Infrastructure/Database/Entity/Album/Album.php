<?php

namespace App\Infrastructure\Database\Entity\Album;

use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Genre\Genre;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Model\Enum\ExternalSourceEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class Album extends BaseEntity
{
    #[Column(unique: true, nullable: true)]
    protected ?string $external_id = null;

    #[Column(nullable: true, enumType: ExternalSourceEnum::class)]
    protected ?string $external_source = null;

    #[Column(nullable: false)]
    protected string $name;

    #[ManyToMany(targetEntity: Track::class, mappedBy: "albums")]
    protected Collection $tracks;

    public function __construct()
    {
        parent::__construct();

        $this->tracks = new ArrayCollection();
    }

    /**
     * @return Collection<int,Track>
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function getExternalId(): string|null
    {
        return $this->external_id;
    }

    public function setExternalId(?string $external_id): Album
    {
        $this->external_id = $external_id;
        return $this;
    }

    public function getExternalSource(): string|null
    {
        return $this->external_source;
    }

    public function setExternalSource(?string $external_source): Album
    {
        $this->external_source = $external_source;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Album
    {
        $this->name = $name;
        return $this;
    }
}