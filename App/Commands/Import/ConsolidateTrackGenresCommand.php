<?php

namespace App\Commands\Import;

use App\Infrastructure\Database\Entity\Track\Track;
use App\Infrastructure\Database\Repository\TrackRepository;
use App\Model\Genre\GenreAssigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'import:consolidate-genres')]
final class ConsolidateTrackGenresCommand extends Command
{
    public function __construct(
        private readonly TrackRepository        $trackRepository,
        private readonly GenreAssigner          $genreAssigner,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting genre consolidation for all tracks...');

        $qb = $this->trackRepository->getBaseQuery();
        $qb->select('u')
            ->leftJoin('u.tags', 'tags')
            ->addSelect('tags');

        /** @var Track[] $tracks */
        $tracks = $qb->getQuery()->getResult();

        $total = count($tracks);
        $output->writeln(sprintf('Found %d tracks to process.', $total));

        $i = 0;
        foreach ($tracks as $track)
        {
            $tags = [];
            foreach ($track->getTags() as $tag)
            {
                $tags[] = $tag->getName();
            }

            if (!empty($tags))
            {
                $this->genreAssigner->assignGenresToTrack($track, $tags);
            }

            $i++;
            if ($i % 100 === 0)
            {
                $this->entityManager->flush();
                $output->writeln(sprintf('Processed %d/%d tracks.', $i, $total));
            }
        }

        $this->entityManager->flush();
        $output->writeln('Consolidation finished.');

        return Command::SUCCESS;
    }
}
