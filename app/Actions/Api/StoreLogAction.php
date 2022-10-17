<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions\Api;

use Bloatless\Endocore\Core\Http\Exception\BadRequestException;
use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;
use Bloatless\Endocore\Domain\Payload;
use Bloatless\Endocore\Responder\JsonResponder;
use Bloatless\Pile\Actions\JsonAction;
use Bloatless\Pile\Domains\AuthDomain;
use Bloatless\Pile\Domains\LogsDomain;

class StoreLogAction extends JsonAction
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

        $rawData = $request->getRawBody();
        if (empty($rawData)) {
            throw new BadRequestException('Request body can not be empty.');
        }

        $logData = json_decode($rawData, true);
        if ($this->logsDomain->validateLogData($logData) !== true) {
            $payload = new Payload(Payload::STATUS_ERROR, ['Invalid log data']);

            return $this->responder->__invoke($request, $payload);
        }

        $attributes = $this->logsDomain->preprocessLogData($logData);
        $attributes['log_id'] = $this->logsDomain->storeLogData($attributes);
        $payload =  new Payload(Payload::STATUS_OK, $attributes);

        return $this->responder->__invoke($request, $payload);
    }
}
