<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions;

use Bloatless\Endocore\Components\PhtmlRenderer\PhtmlRenderer;
use Bloatless\Endocore\Contracts\Responder\ResponderContract;
use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;
use Bloatless\Endocore\Domain\Payload;
use Bloatless\Endocore\Responder\Responder;

class PhtmlResponder extends Responder implements ResponderContract
{
    protected PhtmlRenderer $renderer;

    protected string $view = '';

    public function __construct(PhtmlRenderer $phtmlRenderer)
    {
        parent::__construct();
        $this->response->addHeader('Content-Type', 'text/html; charset=utf-8');

        $this->setRenderer($phtmlRenderer);
    }

    public function __invoke(Request $request, Payload $payload): Response
    {
        $this->response->setBody(
            $this->renderer->render($this->view, $payload->asArray())
        );

        return $this->response;
    }

    public function setRenderer(PhtmlRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }
}
