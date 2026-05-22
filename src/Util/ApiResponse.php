<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse
{

    public static function success(
        mixed $data = [],
        string $message = 'Success',
        array $meta = [],
        int $status = 200
    ): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => [],
            'meta'    => $meta
        ], $status);
    }

    public static function error(
        string $message,
        array $errors = [],
        int $status = 400,
        mixed $data = null
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
            'meta'    => []
        ], $status);
    }
}
