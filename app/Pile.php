<?php

declare(strict_types=1);

namespace Bloatless\Pile;

use Bloatless\Pile\Domains\LogsDomain;

class Pile
{

    protected const ITEMS_PER_PAGE = 100;

    protected LogsDomain $logsDomain;

    public function __construct(
        protected array $config,
    ) {
        $this->logsDomain = new LogsDomain($this->config);
    }

    public function __invoke($request, $server)
    {
        try {
            $routeInfo = $this->route($request, $server);
            $this->dispatch($routeInfo);
        } catch (\Exception $e) {
            // TODO: Add error handling
            var_dump($e);
        }
    }

    protected function route(array $request, array $server): array
    {
        $requestMethod = $server['REQUEST_METHOD'];
        $requestUri = $server['REQUEST_URI'] ?? '';
        $requestUri = parse_url($requestUri, PHP_URL_PATH);

        $routeInfo = [
            'action' => '',
            'params' => [],
        ];

        if ($requestMethod === 'GET') {
            switch ($requestUri) {
                case '/':
                    $routeInfo['action'] = 'show_logs';
                    $routeInfo['params'] = [
                        'source' => $request['s'] ?? [],
                        'level' =>  $request['l'] ?? [],
                        'page' => (int) ($request['page'] ?? 1),
                    ];
                    break;
                case '/api/v1/logstats':
                    $routeInfo['action'] = 'show_log_stats';
                    break;

            }
        }

        if ($requestMethod === 'POST') {
            switch ($requestUri) {
                case '/api/v1/log':
                    $routeInfo['action'] = 'store_log';
                    break;
            }
        }

        return $routeInfo;
    }

    protected function dispatch(array $routeInfo): void
    {
        switch ($routeInfo['action']) {
            case 'show_logs':
                $response = $this->getShowLogsResponse($routeInfo['params']);
                break;
            case 'show_log_stats':
                break;
            case 'store_log':
                break;
            case '':
                // @todo Handle "not found"  or "invalid request".
                break;
        }
    }

    protected function getShowLogsResponse(array $params): array
    {
        $paginationData = $this->getPaginationData($params);
        $logs = $this->logsDomain->getLogData(['*'], $params, $paginationData['limit'], $paginationData['offset']);
        $levels = $this->logsDomain->getErrorLevelList();
        $sources = $this->logsDomain->getSourcesList();

        $payload = [
            'filters' => $params,
            'logs' => $logs,
            'levels' => $levels,
            'sources' => $sources,
            'pagination_data' => $paginationData,
        ];
        var_dump($payload);
    }

    private function getPaginationData(array $params): array
    {
        $page = (int) $params['page'];
        $page = ($page <= 0) ? 1 : $page;
        $offset = (($page - 1) * self::ITEMS_PER_PAGE);
        $itemsTotal = $this->logsDomain->getLogsTotal($params);

        return [
            'current'=> $page,
            'limit' => self::ITEMS_PER_PAGE,
            'offset' => $offset,
            'total' => $itemsTotal,
        ];
    }
}
