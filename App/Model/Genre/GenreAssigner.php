<?php

namespace App\Model\Genre;

use App\Infrastructure\Database\Entity\Genre\Genre;
use App\Infrastructure\Database\Entity\Track\Track;
use Doctrine\ORM\EntityManagerInterface;

final readonly class GenreAssigner
{
    public function __construct(
        private GenreConsolidator      $genreConsolidator,
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * Přiřadí tracku sjednocené žánry ze zadaných tagů.
     * Zajišťuje vytvoření žánru v databázi, pokud ještě neexistuje.
     *
     * @param Track $track
     * @param string[] $tags
     */
    public function assignGenresToTrack(Track $track, array $tags): void
    {
        $genreNames = $this->genreConsolidator->extractGenresFromTags($tags);
        $genreRepository = $this->entityManager->getRepository(Genre::class);

        foreach ($genreNames as $genreName)
        {
            /** @var Genre|null $genre */
            $genre = $genreRepository->findOneBy(['name' => $genreName]);

            if ($genre === null)
            {
                $genre = new Genre();
                $genre->setName($genreName);
                $this->entityManager->persist($genre);
                $this->entityManager->flush();
            }

            if (!$track->getGenres()->contains($genre))
            {
                $track->getGenres()->add($genre);
            }
        }
    }
}
