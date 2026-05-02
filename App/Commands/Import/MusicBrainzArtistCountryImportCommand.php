<?php

namespace App\Commands\Import;

use App\Infrastructure\Import\Artist\MusicBrainzArtistCountryImport;
use App\Infrastructure\Import\Playlist\SpotifyPlaylistImport;
use App\Util\SpotifySourceUrlExtractor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'import:musicbrainz:artist-country')]
final class MusicBrainzArtistCountryImportCommand extends Command
{
    public function __construct(
        private readonly MusicBrainzArtistCountryImport $musicBrainzArtistCountryImport,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Importing artist countries from MusicBrainz');

        $this->musicBrainzArtistCountryImport->import();

        $output->writeln('Import finished.');

        return Command::SUCCESS;
    }
}