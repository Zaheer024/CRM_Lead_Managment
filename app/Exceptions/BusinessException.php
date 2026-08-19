<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A domain/business rule violation that should be returned to the
 * client as a JSON error with an appropriate HTTP status code.
 */
class BusinessException extends Exception
{
    public function __construct(
        string $message,
        protected int $status = 422,
        ?array $errors = null
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->status);
    }
}
