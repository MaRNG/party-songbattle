<?php

namespace App\Commands\Import;

use App\Infrastructure\Import\Playlist\SpotifyPlaylistImport;
use App\Infrastructure\Import\Track\MusicBrainzTrackTagsImport;
use App\Util\SpotifySourceUrlExtractor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'import:musicbrainz:track-tags')]
final class MusicBrainzTrackTagsImportCommand extends Command
{
    public function __construct(
        private readonly MusicBrainzTrackTagsImport $musicBrainzTrackTagsImport,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Importing track tags from MusicBrainz');

        $this->musicBrainzTrackTagsImport->import();

        $output->writeln('Import finished.');

        return Command::SUCCESS;
    }
}