<?php

namespace App\Commands\Track;

use App\Infrastructure\Database\Entity\Track\Track;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Util\CliWriter;
use App\Util\TrackHashGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'tracks:generate-hash')]
final class GenerateTrackNameArtistNameHashesCommand extends Command
{
    public function __construct(
        private readonly TrackRepository        $trackRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tracksQb = $this->trackRepository->getBaseQuery();

        CliWriter::writeNl('Start generating track name hashes...');

        $i = 0;
        $tracks = [];

        /** @var Track $track */
        foreach ($tracksQb->getQuery()->toIterable() as $track)
        {
            CliWriter::writeNl(sprintf('Generating track #%s...', $track->getId()));

            $track->setTrackNameArtistNameHash(TrackHashGenerator::generateTrackNameArtistNameHash($track));

            $this->entityManager->persist($track);

            $tracks[] = $track;
            $i++;

            if (($i % 100) === 0)
            {
                $this->entityManager->flush();

                // Detach jen entity z tohoto batche
                foreach ($tracks as $t)
                {
                    $this->entityManager->detach($t);
                }

                $tracks = [];
                gc_collect_cycles();
            }
        }

        if ($tracks !== [])
        {
            $this->entityManager->flush();

            foreach ($tracks as $t)
            {
                $this->entityManager->detach($t);
            }
        }

        CliWriter::writeNl('Done generating track name hashes.');

        return self::SUCCESS;
    }
}