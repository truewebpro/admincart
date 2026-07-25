<?php

namespace App\Exceptions;

use RuntimeException;

class MissingShopifyScopeException extends RuntimeException
{
    public function __construct(public readonly string $requiredScope, public readonly string $resource)
    {
        parent::__construct(
            "Cannot access {$resource}: missing required scope '{$requiredScope}'."
        );
    }

}
