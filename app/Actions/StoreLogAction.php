<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions;

use Bloatless\Endocore\Action\JsonAction;
use Bloatless\Endocore\Exception\Http\BadRequestException;
use Bloatless\Endocore\Http\Response;
use Bloatless\Pile\Domains\AuthDomain;
use Bloatless\Pile\Domains\LogsDomain;

class StoreLogAction extends JsonAction
{
    /**
     * Stores a new log entity to the database.
     *
     * @param array $arguments
     * @return Response
     * @throws BadRequestException
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function __invoke(array $arguments = []): Response
    {
        $apiKey = (string) $this->request->getServerParam('HTTP_X_API_KEY', '');
        $authDomain = new AuthDomain($this->config, $this->logger);
        if ($authDomain->apiKeyIsValid($apiKey) === false) {
            $response = $this->responder->getResponse();
            $response->setStatus(401);
            return $response;
        }

        $rawData = $this->request->getRawBody();
        if (empty($rawData)) {
            throw new BadRequestException('Request body can not be empty.');
        }

        $logData = json_decode($rawData, true);
        $domain = new LogsDomain($this->config, $this->logger);
        if ($domain->validateLogData($logData) !== true) {
            return $this->responder->error(['Invalid log data.']);
        }

        $attributes = $domain->preprocessLogData($logData);
        $attributes['log_id'] = $domain->storeLogData($attributes);

        return $this->responder->found($attributes);
    }
}
