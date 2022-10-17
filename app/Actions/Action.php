<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions;

use Bloatless\Endocore\Contracts\Action\ActionContract;
use Bloatless\Endocore\Contracts\Responder\ResponderContract;
use Bloatless\Endocore\Core\Http\Request;
use Bloatless\Endocore\Core\Http\Response;
use Bloatless\Pile\Domains\AuthDomain;

abstract class Action implements ActionContract
{
    /**
     * Defines if action requires authentication.
     *
     * @var bool $requiresAuthentication
     */
    protected bool $requiresAuthentication = false;

    protected ResponderContract $responder;

    public function __construct(
        protected AuthDomain $authDomain
    ) {
        // constructor body
    }

    public function setResponder(ResponderContract $responder): void
    {
        $this->responder = $responder;
    }

    public function __invoke(Request $request, array $arguments = []): Response
    {
        if (
            $this->requiresAuthentication === true &&
            $this->authDomain->requestIsAuthorized($request) === false
        ) {
            return $this->authDomain->getRequestAuthResponse();
        }

        return $this->execute($request, $arguments);
    }

    abstract protected function execute(Request $request, array $arguments = []): Response;
}
