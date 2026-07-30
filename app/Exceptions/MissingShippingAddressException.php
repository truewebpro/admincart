<?php

namespace App\Exceptions;

use Exception;

class MissingShippingAddressException extends Exception
{
    public function __construct()
    {
        parent::__construct('A shipping address must be set before this draft order can be converted to a real order.');
    }
}
