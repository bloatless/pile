<?php

declare(strict_types=1);

namespace Bloatless\Pile\Actions;

use Bloatless\Endocore\Responder\JsonResponder;
use Bloatless\Pile\Domains\AuthDomain;

/**
 * @property PhtmlResponder $responder
 */
abstract class JsonAction extends Action
{
    public function __construct(
        AuthDomain $authDomain,
        JsonResponder $responder,
    ) {
        parent::__construct($authDomain);
        $this->setResponder($responder);
    }
}
