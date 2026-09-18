<?php

declare(strict_types=1);

/**
 * ============================================================
 * SIQUA Enterprise
 * ============================================================
 * API RESPONSE
 * ------------------------------------------------------------
 * Standard JSON Response Helper
 * ============================================================
 */

final class ApiResponse
{
    /**
     * ========================================================
     * SUCCESS
     * ========================================================
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200
    ): never {

        http_response_code($status);

        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode(
            [
                'success' => true,
                'message' => $message,
                'data'    => $data
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    /**
     * ========================================================
     * ERROR
     * ========================================================
     */
    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null
    ): never {

        http_response_code($status);

        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode(
            [
                'success' => false,
                'message' => $message,
                'errors'  => $errors
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    /**
     * ========================================================
     * EXCEPTION
     * ========================================================
     */
    public static function exception(
        Throwable $e
    ): never {

        self::error(

            $e->getMessage(),

            $e instanceof InvalidArgumentException
                ? 422
                : 400

        );

    }

}