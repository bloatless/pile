<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions\Website;

use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;
use Bloatless\Endocore\Domain\Payload;
use Bloatless\Pile\Actions\PhtmlAction;
use Bloatless\Pile\Domains\AuthDomain;
use Bloatless\Pile\Domains\LogsDomain;

class ShowLogsAction extends PhtmlAction
{
    protected bool $requiresAuthentication = true;

    private const ITEMS_PER_PAGE = 100;

    private LogsDomain $logsDomain;

    public function __construct(AuthDomain $authDomain, LogsDomain $logsDomain, ShowLogsResponder $responder)
    {
        parent::__construct($authDomain, $responder);
        $this->logsDomain = $logsDomain;
    }

    /**
     * Displays a list of logs.
     *
     * @param array $arguments
     * @return Response
     */
    public function execute(Request $request, array $arguments = []): Response
    {
        // collect data
        $filters = $this->getFilters($request);
        $paginationData = $this->getPaginationData($request, $filters);
        $logs = $this->logsDomain->getLogData(['*'], $filters, $paginationData['limit'], $paginationData['offset']);
        $levels = $this->logsDomain->getErrorLevelList();
        $sources = $this->logsDomain->getSourcesList();

        $payload = new Payload(Payload::STATUS_OK, [
            'filters' => $filters,
            'logs' => $logs,
            'levels' => $levels,
            'sources' => $sources,
            'pagination_data' => $paginationData,
        ]);

        return $this->responder->__invoke($request, $payload);
    }

    /**
     * Fetches filters from request.
     *
     * @return array
     */
    private function getFilters(Request $request): array
    {
        return [
            'source' => $request->getParam('s', []),
            'level' => $request->getParam('l', []),
        ];
    }

    /**
     * Provides data required for pagination.
     *
     * @param array $filters
     * @return array
     */
    private function getPaginationData(Request $request, array $filters): array
    {
        $page = (int) $request->getParam('page', 1);
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
