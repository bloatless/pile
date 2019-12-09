<?php

declare(strict_types=1);

namespace Bloatless\Pile\Responder;

use Bloatless\Endocore\Http\Request;
use Bloatless\Endocore\Http\Response;
use Bloatless\Endocore\Responder\HtmlResponder;
use Bloatless\Endocore\Components\PhtmlRenderer\Factory as RendererFactory;

class ShowLogsResponder extends HtmlResponder
{
    /**
     * @var \Bloatless\Endocore\Components\PhtmlRenderer\PhtmlRenderer $renderer
     */
    protected $renderer;

    public function __construct(array $config)
    {
        $this->renderer = (new RendererFactory($config))->makeRenderer();
        parent::__construct($config);
    }

    /**
     * Renders the "logs" page and returns response.
     *
     * @param Request $request
     * @param array $data
     * @return Response
     * @throws \Bloatless\Endocore\Components\PhtmlRenderer\TemplatingException
     */
    public function __invoke(Request $request, array $data): Response
    {
        $body = $this->renderer->render('logs', [
            'logs' => $data['logs'] ?? [],
            'sources' => $data['sources'] ?? [],
            'levels' => $data['levels'] ?? [],
            'filters' => $data['filters'] ?? [],
            'pagination' => $this->providePagination($request, $data['pagination_data']),
        ]);

        return $this->found(['body' => $body]);
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
