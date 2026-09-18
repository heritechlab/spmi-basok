<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| File : core/BaseApi.php
|--------------------------------------------------------------------------
*/

abstract class BaseApi
{
    public function __construct()
    {
        header('Content-Type: application/json; charset=UTF-8');
    }

    /*
    |--------------------------------------------------------------------------
    | JSON Response
    |--------------------------------------------------------------------------
    */

    protected function json(array $response, int $httpCode = 200): never
    {
        http_response_code($httpCode);

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    protected function success(
        string $message = '',
        array $data = [],
        int $httpCode = 200
    ): never {

        $this->json(
            [
                'success' => true,
                'message' => $message,
                'data'    => $data
            ],
            $httpCode
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Error
    |--------------------------------------------------------------------------
    */

    protected function error(
        string $message,
        int $httpCode = 400,
        array $errors = []
    ): never {

        $this->json(
            [
                'success' => false,
                'message' => $message,
                'errors'  => $errors
            ],
            $httpCode
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Request Method
    |--------------------------------------------------------------------------
    */

    protected function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {

            $this->error(
                'Method Not Allowed',
                405
            );

        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST
    |--------------------------------------------------------------------------
    */

    protected function post(): array
    {
        return $_POST;
    }

    /*
    |--------------------------------------------------------------------------
    | GET
    |--------------------------------------------------------------------------
    */

    protected function get(): array
    {
        return $_GET;
    }

    /*
    |--------------------------------------------------------------------------
    | Required Field
    |--------------------------------------------------------------------------
    */

    protected function required(
        array $source,
        array $fields
    ): void {

        foreach ($fields as $field) {

            if (
                !isset($source[$field]) ||
                trim((string)$source[$field]) === ''
            ) {

                $this->error(
                    "Field '{$field}' wajib diisi.",
                    422
                );

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Exception Handler
    |--------------------------------------------------------------------------
    */

    protected function execute(callable $callback): never
    {
        try {

            $callback();

        } catch (Throwable $e) {

            $this->error(
                $e->getMessage(),
                500
            );

        }

        exit;

    }
}