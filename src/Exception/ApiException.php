<?php

declare(strict_types=1);

namespace App\Exception;

final class ApiException extends \RuntimeException
{
    /**
     * @param string $message error message
     * @param int    $status  HTTP status code
     * @param array  $errors  Detailed validation or domain errors
     */
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly array $errors = []
    ) {
        parent::__construct($message);
    }

    /**
     * Returns HTTP status code for this exception.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Returns detailed error messages.
     *
     * @return array<int,string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
