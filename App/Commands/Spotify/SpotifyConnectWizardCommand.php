<?php

namespace App\Commands\Spotify;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "spotify:connect", description: "Connect to Spotify.")]
final class SpotifyConnectWizardCommand extends Command
{
    private const string SCOPE = 'user-read-private user-read-email';

    public function __construct(
        private readonly string $spotifyClientId,
        private readonly string $spotifyClientSecret,
        private readonly string $spotifyAppRedirectUri,
    ) {
        parent::__construct(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
        $io->title('Spotify Connect Wizard');

        if (empty($this->spotifyClientId) || empty($this->spotifyClientSecret)) {
            $io->error('Spotify Client ID or Client Secret is missing in configuration (local.neon).');
            return Command::FAILURE;
        }

        $state = bin2hex(random_bytes(8));
        $authUrl = $this->createSpotifyAuthUrl($state);

        $io->section('Step 1: Authorization');
        $io->writeln('Please open the following URL in your browser and authorize the application:');
        $io->note($authUrl);

        $callbackUrl = $io->ask('Step 2: Paste the full redirect URL here (the one you were redirected to)');

        if (!$callbackUrl) {
            $io->error('No URL provided.');
            return Command::FAILURE;
        }

        $code = $this->extractCodeFromUrl($callbackUrl);
        if (!$code) {
            $io->error('Could not find "code" in the provided URL.');
            return Command::FAILURE;
        }

        $io->section('Step 3: Exchanging code for tokens');
        try {
            $tokens = $this->exchangeCodeForTokens($code);
            $io->success('Tokens obtained successfully!');
            
            $io->section('Step 4: Saving tokens to local.neon');
            $this->saveTokensToConfig($tokens);
            $io->success('Tokens saved to config/local.neon');

        } catch (\Exception $e) {
            $io->error(sprintf('Error during token exchange: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createSpotifyAuthUrl(string $state): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->spotifyClientId,
            'scope' => self::SCOPE,
            'redirect_uri' => $this->spotifyAppRedirectUri,
            'state' => $state,
        ];

        return 'https://accounts.spotify.com/authorize?' . http_build_query($params);
    }

    private function extractCodeFromUrl(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query) {
            return null;
        }

        parse_str($query, $params);
        return $params['code'] ?? null;
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    private function exchangeCodeForTokens(string $code): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://accounts.spotify.com/api/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->spotifyClientId . ':' . $this->spotifyClientSecret),
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->spotifyAppRedirectUri,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        if (!isset($data['access_token'])) {
            throw new \RuntimeException('Invalid response from Spotify: ' . json_encode($data));
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? '',
        ];
    }

    private function saveTokensToConfig(array $tokens): void
    {
        $configPath = __DIR__ . '/../../../config/local.neon';
        if (!file_exists($configPath)) {
            throw new \RuntimeException(sprintf('Config file not found at %s', $configPath));
        }

        $content = file_get_contents($configPath);
        $neon = \Nette\Neon\Neon::decode($content);

        $neon['parameters']['spotify']['auth']['accessToken'] = $tokens['access_token'];
        $neon['parameters']['spotify']['auth']['refreshToken'] = $tokens['refresh_token'];

        file_put_contents($configPath, \Nette\Neon\Neon::encode($neon, \Nette\Neon\Neon::BLOCK));
    }
}