<?php

namespace App\Infrastructure\Database\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

abstract class BaseRepository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(static::getEntityClass())
        );
    }

    abstract protected static function getEntityClass(): string;
}