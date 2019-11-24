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
            'current_page' => $data['pagination_data']['current'],
            'pagination' => $this->providePaginationLinks($request, $data['pagination_data']),
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
    protected function providePaginationLinks(Request $request, array $paginationData): array
    {
        $requestUri = $request->getRequestUri();
        $urlQuery = parse_url($requestUri, PHP_URL_QUERY);
        $urlQuery = $urlQuery ?? '';
        parse_str($urlQuery, $params);

        $pages = ceil($paginationData['total'] / $paginationData['limit']);
        $paginationLinks = [];
        for ($i = 1; $i <= $pages; $i++) {
            $params['page'] = $i;
            $paginationLinks[$i] = '/?' . http_build_query($params);
        }

        return $paginationLinks;
    }
}
