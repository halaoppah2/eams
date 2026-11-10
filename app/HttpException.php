<?php

namespace App;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    private int $statusCode;

    public function __construct(
        string $message,
        int $statusCode,
        ?Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
