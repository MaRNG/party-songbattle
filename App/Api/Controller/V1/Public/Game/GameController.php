<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Public\Game;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Controller\V1\Public\BasePublicV1Controller;
use App\Api\Facade\Game\GameFacade;
use App\Infrastructure\Database\Entity\Game\Game;
use App\Infrastructure\Database\Entity\Game\GamePlayer;
use App\Model\Enum\GameModeEnum;
use App\Model\Game\Dto\GameFilterListDto;
use App\Model\Game\Dto\GameFilterOptionsDto;
use App\Model\Game\Dto\GameGuessResultDto;
use App\Model\Game\Dto\GamePlayerStateDto;
use App\Model\Game\Dto\GameRoundResultDto;
use App\Model\Game\Dto\GameSessionDto;
use App\Model\Game\Dto\GameStateDto;
use App\Model\Game\Dto\GameTrackInfoDto;
use App\Model\Game\GameRules;
use GuzzleHttp\Psr7\LimitStream;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

#[Path('/songbattle')]
final class GameController extends BasePublicV1Controller
{
    public function __construct(private readonly GameFacade $gameFacade)
    {
    }

    #[Path('/filters')]
    #[Method('GET')]
    public function filters(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $options = $this->gameFacade->getFilterOptions($this->createFilterListDto($request->getQueryParams()));

        return $response->writeJsonBody($this->serializeFilterOptions($options));
    }

    #[Path('/games')]
    #[Method('POST')]
    public function create(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $body = (array)$request->getJsonBody();

        $session = $this->gameFacade->create(
            $this->createFilterListDto($body),
            $this->parseMode($body['mode'] ?? null),
            $this->parseName($body['name'] ?? null),
            $this->parsePointsPerStep($body['pointsPerStep'] ?? null),
            (bool)($body['showLeaderboardToPlayers'] ?? true),
        );

        return $response->writeJsonBody($this->serializeSession($session));
    }

    #[Path('/games/{hash}/join')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function join(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $body = (array)$request->getJsonBody();

        $session = $this->gameFacade->join(
            (string)$request->getParameter('hash'),
            $this->parseName($body['name'] ?? null),
        );

        return $response->writeJsonBody($this->serializeSession($session));
    }

    #[Path('/games/join')]
    #[Method('POST')]
    public function joinByCode(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $body = (array)$request->getJsonBody();

        $code = trim((string)($body['code'] ?? ''));

        if ($code === '')
        {
            throw new ClientErrorException('Invite code is required', 400);
        }

        $session = $this->gameFacade->joinByCode(
            $code,
            $this->parseName($body['name'] ?? null),
        );

        return $response->writeJsonBody($this->serializeSession($session));
    }

    #[Path('/games/{hash}/state')]
    #[Method('GET')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function state(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->getState((string)$request->getParameter('hash'), $this->parseToken($request));

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/start')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function start(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->start((string)$request->getParameter('hash'), $this->parseToken($request));

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/playback')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function playback(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $body = (array)$request->getJsonBody();

        $state = $this->gameFacade->setPlaying(
            (string)$request->getParameter('hash'),
            $this->parseToken($request),
            (bool)($body['playing'] ?? false),
        );

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/skip')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function skip(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->skip((string)$request->getParameter('hash'), $this->parseToken($request));

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/next')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function next(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->nextSong((string)$request->getParameter('hash'), $this->parseToken($request));

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/restart')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function restart(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->restart((string)$request->getParameter('hash'), $this->parseToken($request));

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/continue')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function continueRound(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->continueRound((string)$request->getParameter('hash'), $this->parseToken($request));

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/guess')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function guess(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $body = (array)$request->getJsonBody();

        $result = $this->gameFacade->submitGuess(
            (string)$request->getParameter('hash'),
            $this->parseToken($request),
            (int)($body['trackId'] ?? 0),
        );

        return $response->writeJsonBody($this->serializeGuessResult($result));
    }

    #[Path('/games/{hash}/pass')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function pass(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->passRound(
            (string)$request->getParameter('hash'),
            $this->parseToken($request),
        );

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/players/{playerId}/kick')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    #[RequestParameter(name: 'playerId', type: 'int', in: 'path')]
    public function kickPlayer(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $state = $this->gameFacade->kickPlayer(
            (string)$request->getParameter('hash'),
            $this->parseToken($request),
            (int)$request->getParameter('playerId'),
        );

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/players/{playerId}/score')]
    #[Method('POST')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    #[RequestParameter(name: 'playerId', type: 'int', in: 'path')]
    public function setPlayerScore(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $body = (array)$request->getJsonBody();

        $state = $this->gameFacade->setPlayerScore(
            (string)$request->getParameter('hash'),
            $this->parseToken($request),
            (int)$request->getParameter('playerId'),
            (int)($body['score'] ?? 0),
        );

        return $response->writeJsonBody($this->serializeState($state));
    }

    #[Path('/games/{hash}/suggest')]
    #[Method('GET')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    public function suggest(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $query = (string)($request->getQueryParams()['q'] ?? '');

        $suggestions = $this->gameFacade->suggestTracks(
            (string)$request->getParameter('hash'),
            $this->parseToken($request),
            $query,
        );

        return $response->writeJsonBody([
            'suggestions' => array_map($this->serializeTrackInfo(...), $suggestions),
        ]);
    }

    #[Path('/games/{hash}/tracks/{gameTrackId}/audio')]
    #[Method('GET')]
    #[RequestParameter(name: 'hash', type: 'string', in: 'path')]
    #[RequestParameter(name: 'gameTrackId', type: 'int', in: 'path')]
    public function trackAudio(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        // A plain <audio src="..."> request is issued by the browser's media engine, which
        // cannot attach the X-Player-Token header used everywhere else — the token travels
        // as a query parameter here instead, same trade-off any signed-media-URL scheme makes.
        $path = $this->gameFacade->getTrackAudioPath(
            (string)$request->getParameter('hash'),
            $this->parseAudioToken($request),
            (int)$request->getParameter('gameTrackId'),
        );

        return $this->streamAudioFile($path, $request, $response);
    }

    private function streamAudioFile(string $path, ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        $size = filesize($path);
        $rangeHeader = $request->getHeaderLine('Range');

        $start = 0;
        $end = $size - 1;
        $status = ApiResponse::S200_OK;

        if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches) === 1 && $size > 0)
        {
            if ($matches[1] !== '')
            {
                $start = (int)$matches[1];
            }

            if ($matches[2] !== '')
            {
                $end = (int)$matches[2];
            }

            $end = min($end, $size - 1);

            if ($start > $end || $start >= $size)
            {
                return $response
                    ->withStatus(ApiResponse::S416_REQUESTED_RANGE_NOT_SATISFIABLE)
                    ->withHeader('Content-Range', "bytes */{$size}");
            }

            $status = ApiResponse::S206_PARTIAL_CONTENT;
        }

        $length = $end - $start + 1;

        $handle = fopen($path, 'rb');
        $stream = new LimitStream(new Stream($handle), $length, $start);

        $response = $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'audio/mpeg')
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Content-Length', (string)$length)
            ->withHeader('Cache-Control', 'private, no-store')
            ->withBody($stream);

        if ($status === ApiResponse::S206_PARTIAL_CONTENT)
        {
            $response = $response->withHeader('Content-Range', "bytes {$start}-{$end}/{$size}");
        }

        return $response;
    }

    private function parseAudioToken(ApiRequest $request): string
    {
        $token = (string)($request->getQueryParams()['token'] ?? '');

        if ($token === '')
        {
            throw new ClientErrorException('Missing token query parameter', 401);
        }

        return $token;
    }

    private function parseToken(ApiRequest $request): string
    {
        $token = $request->getHeaderLine('X-Player-Token');

        if ($token === '')
        {
            throw new ClientErrorException('Missing X-Player-Token header', 401);
        }

        return $token;
    }

    private function parseName(mixed $name): string
    {
        $name = trim((string)$name);

        if ($name === '')
        {
            throw new ClientErrorException('Player name is required', 422);
        }

        return $name;
    }

    private function parseMode(mixed $mode): GameModeEnum
    {
        $mode = GameModeEnum::tryFrom((string)$mode);

        if ($mode === null)
        {
            throw new ClientErrorException('Invalid game mode', 422);
        }

        return $mode;
    }

    /**
     * @return int[]
     */
    private function parsePointsPerStep(mixed $pointsPerStep): array
    {
        if (!is_array($pointsPerStep) || count($pointsPerStep) !== count(GameRules::STEPS))
        {
            return GameRules::DEFAULT_POINTS_PER_STEP;
        }

        return array_values(array_map(static fn (mixed $points): int => max(0, (int)$points), $pointsPerStep));
    }

    /**
     * @param array<string,mixed> $source
     */
    private function createFilterListDto(array $source): GameFilterListDto
    {
        return new GameFilterListDto(
            year_filter  : $this->toIntArray($source['years'] ?? $source['year_filter'] ?? []),
            genre_filter : $this->toIntArray($source['genres'] ?? $source['genre_filter'] ?? []),
            area_filter  : $this->toStringArray($source['areas'] ?? $source['area_filter'] ?? []),
            artist_filter: $this->toIntArray($source['artists'] ?? $source['artist_filter'] ?? []),
        );
    }

    private function toIntArray(mixed $value): array
    {
        return array_values(array_map('intval', is_array($value) ? $value : []));
    }

    private function toStringArray(mixed $value): array
    {
        return array_values(array_map('strval', is_array($value) ? $value : []));
    }

    private function serializeFilterOptions(GameFilterOptionsDto $dto): array
    {
        return [
            'decades'   => $dto->decades,
            'genres'    => $dto->genres,
            'areas'     => $dto->areas,
            'poolCount' => $dto->poolCount,
        ];
    }

    private function serializeSession(GameSessionDto $dto): array
    {
        return [
            'game'   => $this->serializeGame($dto->game),
            'player' => $this->serializePlayer($dto->player),
        ];
    }

    private function serializeGame(Game $game): array
    {
        return [
            'code' => $game->getCode(),
            'hash' => $game->getHash(),
            'mode' => $game->getMode()->value,
        ];
    }

    private function serializePlayer(GamePlayer $player): array
    {
        return [
            'token'    => $player->getToken(),
            'name'     => $player->getName(),
            'initials' => $player->getInitials(),
            'color'    => $player->getColor(),
            'role'     => $player->getRole()->value,
        ];
    }

    private function serializeState(GameStateDto $dto): array
    {
        return [
            'code'          => $dto->code,
            'hash'          => $dto->hash,
            'mode'          => $dto->mode->value,
            'status'        => $dto->status->value,
            'viewerRole'    => $dto->viewerRole->value,
            'isPlaying'     => $dto->isPlaying,
            'elapsedSeconds' => $dto->elapsedSeconds,
            'stepSeconds'   => $dto->stepSeconds,
            'stepIndex'     => $dto->stepIndex,
            'totalSteps'    => $dto->totalSteps,
            'trackPosition' => $dto->trackPosition,
            'totalTracks'   => $dto->totalTracks,
            'track'         => $dto->track === null ? null : $this->serializeTrackInfo($dto->track),
            'previousTrack' => $dto->previousTrack === null ? null : $this->serializeTrackInfo($dto->previousTrack),
            'audioTrackId'  => $dto->audioTrackId,
            'roundResult'   => $dto->roundResult === null ? null : $this->serializeRoundResult($dto->roundResult),
            'showLeaderboardToPlayers' => $dto->showLeaderboardToPlayers,
            'players'       => array_map($this->serializePlayerState(...), $dto->players),
            'revealAutoContinueInSeconds' => $dto->revealAutoContinueInSeconds,
        ];
    }

    private function serializeRoundResult(GameRoundResultDto $dto): array
    {
        return [
            'correct'     => $dto->correct,
            'guesserName' => $dto->guesserName,
            'atSeconds'   => $dto->atSeconds,
            'points'      => $dto->points,
            'streak'      => $dto->streak,
            'score'       => $dto->score,
        ];
    }

    private function serializePlayerState(GamePlayerStateDto $player): array
    {
        return [
            'id'        => $player->id,
            'name'      => $player->name,
            'initials'  => $player->initials,
            'color'     => $player->color,
            'role'      => $player->role->value,
            'score'     => $player->score,
            'streak'    => $player->streak,
            'guesses'   => $player->guesses,
            'connected' => $player->connected,
            'isViewer'  => $player->isViewer,
            'isCurrentTurn' => $player->isCurrentTurn,
            'attemptsRemaining' => $player->attemptsRemaining,
            'answeredCorrectly' => $player->answeredCorrectly,
            'hasPassed' => $player->hasPassed,
        ];
    }

    private function serializeTrackInfo(GameTrackInfoDto $dto): array
    {
        return [
            'trackName'      => $dto->trackName,
            'artistName'     => $dto->artistName,
            'audioTrackId'   => $dto->audioTrackId,
            'id'             => $dto->id,
        ];
    }

    private function serializeGuessResult(GameGuessResultDto $dto): array
    {
        return [
            'correct'   => $dto->correct,
            'roundOver' => $dto->roundOver,
            'atSeconds' => $dto->atSeconds,
            'points'    => $dto->points,
            'score'     => $dto->score,
            'streak'    => $dto->streak,
        ];
    }
}
