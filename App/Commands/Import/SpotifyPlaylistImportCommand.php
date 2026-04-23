<?php

namespace App\Commands\Import;

use App\Infrastructure\Import\Playlist\SpotifyPlaylistImport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'import:spotify:playlist')]
final class SpotifyPlaylistImportCommand extends Command
{
    public function __construct(
        private readonly SpotifyPlaylistImport $spotifyPlaylistImport,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $playlistId = $input->getArgument('playlistId');

        $output->writeln(sprintf('Importing Playlist: %s', $playlistId));

        $this->spotifyPlaylistImport->importPlaylistTracks($playlistId);

        $output->writeln('Import finished.');

        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this->addArgument('playlistId', InputArgument::REQUIRED, 'Playlist Spotify ID');
    }
}