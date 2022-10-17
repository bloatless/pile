<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions\Website;

use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;
use Bloatless\Endocore\Domain\Payload;
use Bloatless\Pile\Actions\PhtmlResponder;

class ShowLogsResponder extends PhtmlResponder
{

    protected string $view = 'logs';

    /**
     * Renders the "logs" page and returns response.
     *
     * @param Request $request
     * @param array $data
     * @return Response
     * @throws \Bloatless\Endocore\Components\PhtmlRenderer\TemplatingException
     */
    public function __invoke(Request $request, Payload $payload): Response
    {
        $payload['pagination'] = $this->providePagination($request, $payload['pagination_data']);
        $this->response->setBody(
            $this->renderer->render($this->view, $payload->asArray())
        );

        return $this->response;
    }

    /**
     * Generates the pagination links.
     *
     * @param Request $request
     * @param array $paginationData
     * @return array
     */
    protected function providePagination(Request $request, array $paginationData): array
    {
        $requestUri = $request->getRequestUri();
        $urlQuery = parse_url($requestUri, PHP_URL_QUERY);
        $urlQuery = $urlQuery ?? '';
        parse_str($urlQuery, $params);

        $pages = (int) ceil($paginationData['total'] / $paginationData['limit']);
        $current = (int) $paginationData['current'];
        $pagination = [
            'pages' => $pages,
            'total' => (int) $paginationData['total'],
            'current' => $current,
        ];

        if ($pages >= 1) {
            $pagination['first'] = '/?' . http_build_query(array_merge($params, ['page' => 1]));
            $pagination['last'] = '/?' . http_build_query(array_merge($params, ['page' => $pages]));
            $pagination['prev'] = '';
            if ($current > 1) {
                $pagination['prev'] = '/?' . http_build_query(array_merge($params, ['page' => $current - 1]));
            }
            $pagination['next'] = '';
            if ($current < $pages) {
                $pagination['next'] = '/?' . http_build_query(array_merge($params, ['page' => $current + 1]));
            }
        }

        return $pagination;
    }
}
