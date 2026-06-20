<?php

namespace App\Exceptions;

use Exception;

class ApiResponseException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly mixed $data = null,
    ) {
        parent::__construct($message);
    }
}
