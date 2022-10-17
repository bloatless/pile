<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions;

use Bloatless\Pile\Domains\AuthDomain;

/**
 * @property PhtmlResponder $responder
 */
abstract class PhtmlAction extends Action
{
    public function __construct(
        AuthDomain $authDomain,
        PhtmlResponder $responder,
    ) {
        parent::__construct($authDomain);
        $this->setResponder($responder);
    }

    protected function setView(string $view): void
    {
        $this->responder->setView($view);
    }
}
