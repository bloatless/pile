<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions\Api;

use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;
use Bloatless\Endocore\Domain\Payload;
use Bloatless\Endocore\Responder\JsonResponder;
use Bloatless\Pile\Actions\JsonAction;
use Bloatless\Pile\Domains\AuthDomain;
use Bloatless\Pile\Domains\LogsDomain;

class GetLogStatsAction extends JsonAction
{
    private LogsDomain $logsDomain;

    protected bool $requiresAuthentication = false;

    public function __construct(AuthDomain $authDomain, LogsDomain $logsDomain, JsonResponder $responder)
    {
        parent::__construct($authDomain, $responder);
        $this->logsDomain = $logsDomain;
    }

    /**
     * Stores a new log entity to the database.
     *
     * @param array $arguments
     * @return Response
     */
    public function execute(Request $request, array $arguments = []): Response
    {
        $apiKey = (string) $request->getServerParam('HTTP_X_API_KEY', '');
        if ($this->authDomain->apiKeyIsValid($apiKey) === false) {
            $response = $this->responder->getResponse();
            $response->setStatus(401);

            return $response;
        }

        $filters = $this->getFilters($request);
        $stats = $this->logsDomain->getLogStats($filters);

        $payload = new Payload(Payload::STATUS_OK, [
            'stats' => $stats,
        ]);

        return $this->responder->__invoke($request, $payload);
    }

    private function getFilters(Request $request): array
    {
        return [
            'from' => $request->getParam('from', date('%Y-%m-%d')),
            'to' => $request->getParam('to', date('%Y-%m-%d')),
        ];
    }
}
