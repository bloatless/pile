<?php

declare(strict_types=1);

namespace Bloatless\Pile\Domains;

use Bloatless\Endocore\Components\BasicAuth\BasicAuth;
use Bloatless\Endocore\Components\BasicAuth\Factory as AuthFactory;
use Bloatless\Endocore\Components\Logger\LoggerInterface;
use Bloatless\Endocore\Http\Request;
use Bloatless\Endocore\Http\Response;

class AuthDomain
{
    /**
     * @var array $config
     */
    protected $config;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var BasicAuth $basicAuth
     */
    protected $basicAuth = null;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Checks if given API-Key is valid.
     *
     * @param string $apiKey
     * @return bool
     */
    public function apiKeyIsValid(string $apiKey): bool
    {
        $validKeys = $this->config['auth']['api_keys'] ?? [];

        return in_array($apiKey, $validKeys);
    }

    /**
     * Checks if the given request is authorized.
     *
     * @param Request $request
     * @return bool
     */
    public function requestIsAuthorized(Request $request): bool
    {
        return $this->povideAuth()->isAuthenticated($request);
    }

    /**
     * Renerates and returns a auth-request response.
     *
     * @return Response
     */
    public function getRequestAuthResponse(): Response
    {
        return $this->povideAuth()->requestAuthorization();
    }

    /**
     * Provides a basic-auth instance.
     *
     * @return BasicAuth
     */
    protected function povideAuth(): BasicAuth
    {
        if (!empty($this->basicAuth)) {
            return $this->basicAuth;
        }

        $authFactory = new AuthFactory($this->config);
        $this->basicAuth = $authFactory->makeAuth();

        return $this->basicAuth;
    }
}
