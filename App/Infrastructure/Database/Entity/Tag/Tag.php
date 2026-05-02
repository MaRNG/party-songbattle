<?php

namespace App\Infrastructure\Database\Entity\Tag;

use App\Infrastructure\Database\Entity\Artist\Album;
use App\Infrastructure\Database\Entity\BaseEntity;
use App\Infrastructure\Database\Entity\Track\Track;
use App\Model\Enum\ExternalSourceEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;

#[Entity]
class Tag extends BaseEntity
{
    #[Column(nullable: true, enumType: ExternalSourceEnum::class)]
    protected ?ExternalSourceEnum $external_source = null;

    #[Column(nullable: false, unique: true)]
    protected string $name;

    #[ManyToMany(targetEntity: Track::class, mappedBy: "tags")]
    protected Collection $tracks;

    public function __construct()
    {
        parent::__construct();

        $this->tracks = new ArrayCollection();
    }

    public function getExternalSource(): ExternalSourceEnum|null
    {
        return $this->external_source;
    }

    public function setExternalSource(?ExternalSourceEnum $external_source): Tag
    {
        $this->external_source = $external_source;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Tag
    {
        $this->name = $name;
        return $this;
    }

    public function getTracks(): Collection
    {
        return $this->tracks;
    }
}