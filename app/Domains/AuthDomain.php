<?php

declare(strict_types=1);

namespace Bloatless\Pile\Domains;

use Bloatless\Endocore\Components\BasicAuth\BasicAuth;
use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;

class AuthDomain
{
    /**
     * @var BasicAuth $basicAuthService
     */
    protected BasicAuth $basicAuthService;

    public function __construct(BasicAuth $basicAuthService)
    {
        $this->basicAuthService = $basicAuthService;
    }

    /**
     * Checks if the given request is authorized.
     *
     * @param Request $request
     * @return bool
     */
    public function requestIsAuthorized(Request $request): bool
    {
        return $this->basicAuthService->isAuthenticated($request);
    }

    /**
     * Generates and returns a auth-request response.
     *
     * @return Response
     */
    public function getRequestAuthResponse(): Response
    {
        return $this->basicAuthService->requestAuthorization();
    }

    /**
     * Checks if given API-Key is valid.
     *
     * @param string $apiKey
     * @return bool
     */
    public function apiKeyIsValid(string $apiKey): bool
    {
        $config = include __DIR__ . '/../../config/config.php';
        $validKeys = $config['auth']['api_keys'] ?? [];

        return in_array($apiKey, $validKeys);
    }
}
