<?php

declare(strict_types=1);

namespace App\Api\Controller\V1\Public\Config;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Api\Controller\V1\Public\BasePublicV1Controller;
use Psr\Http\Message\ResponseInterface;

#[Path('/spotify')]
final class SpotifyConfigController extends BasePublicV1Controller
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $redirectUri,
    )
    {
    }

    #[Path('/config')]
    #[Method('GET')]
    public function config(ApiRequest $request, ApiResponse $response): ResponseInterface
    {
        return $response->writeJsonBody([
            'clientId'    => $this->clientId,
            'redirectUri' => $this->redirectUri,
        ]);
    }
}
