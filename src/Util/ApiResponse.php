<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse
{
    public static function success(
        $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $status);
    }

    public static function error(
        string $message = 'Error',
        array $errors = [],
        int $status = 400
    ): JsonResponse {

        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
