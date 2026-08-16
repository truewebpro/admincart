<?php

namespace App\Exceptions;

use Exception;
class SendcloudApiException extends Exception
{
    protected int $statusCode;
    protected ?array $errorBody;

    public function __construct(string $message, int $statusCode = 500, ?array $errorBody = null)
    {
        parent::__construct($message);

        $this->statusCode = $statusCode;
        $this->errorBody = $errorBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorBody(): ?array
    {
        return $this->errorBody;
    }
}
