<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions;

use Bloatless\Endocore\Action\Action;
use Bloatless\Endocore\Http\Response;
use Bloatless\Pile\Domains\AuthDomain;
use Bloatless\Pile\Domains\LogsDomain;
use Bloatless\Pile\Responder\ShowLogsResponder;

class ShowLogsAction extends Action
{
    private const ITEMS_PER_PAGE = 100;

    /**
     * @var AuthDomain $authDomain
     */
    private $authDomain;

    /**
     * @var LogsDomain $logsDomain
     */
    private $logsDomain;

    /**
     * Displays a list of logs.
     *
     * @param array $arguments
     * @return Response
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    public function __invoke(array $arguments = []): Response
    {
        $this->authDomain = new AuthDomain($this->config, $this->logger);
        $this->logsDomain = new LogsDomain($this->config, $this->logger);

        // authorize request
        if ($this->authDomain->requestIsAuthorized($this->request) === false) {
            return $this->authDomain->getRequestAuthResponse();
        }

        // collect data
        $filters = $this->getFilters();
        $paginationData = $this->getPaginationData($filters);
        $logs = $this->logsDomain->getLogData(['*'], $filters, $paginationData['limit'], $paginationData['offset']);
        $levels = $this->logsDomain->getErrorLevelList();
        $sources = $this->logsDomain->getSourcesList();

        // invoke responder
        $this->setResponder((new ShowLogsResponder($this->config)));

        return $this->responder->__invoke($this->request, [
            'filters' => $filters,
            'logs' => $logs,
            'levels' => $levels,
            'sources' => $sources,
            'pagination_data' => $paginationData,
        ]);
    }

    /**
     * Fetches filters from request.
     *
     * @return array
     */
    private function getFilters(): array
    {
        return [
            'source' => $this->request->getParam('s', []),
            'level' => $this->request->getParam('l', []),
        ];
    }

    /**
     * Provides data required for pagination.
     *
     * @param array $filters
     * @return array
     * @throws \Bloatless\Endocore\Components\QueryBuilder\Exception\DatabaseException
     */
    private function getPaginationData(array $filters): array
    {
        $page = (int) $this->request->getParam('page', 1);
        $page = ($page <= 0) ? 1 : $page;
        $offset = (($page - 1) * self::ITEMS_PER_PAGE);
        $itemsTotal = $this->logsDomain->getLogsTotal($filters);

        return [
            'current'=> $page,
            'limit' => self::ITEMS_PER_PAGE,
            'offset' => $offset,
            'total' => $itemsTotal,
        ];
    }
}
