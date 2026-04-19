<?php

namespace App\Infrastructure\Database\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;

#[MappedSuperclass]
abstract class BaseEntity
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private ?int $id;

    #[Column(type: 'datetime')]
    private \DateTimeInterface $created;

    public function __construct()
    {
        $this->id = null;
        $this->created = new \DateTime();
    }

    /**
     * @return int | null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }
}