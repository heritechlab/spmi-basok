<?php

declare(strict_types=1);

/**
 * ============================================================
 * SIQUA Enterprise
 * ============================================================
 * HTTP REQUEST
 * ============================================================
 */

final class Request
{
    /**
     * GET
     */
    public static function get(
        string $key,
        mixed $default = null
    ): mixed {

        return $_GET[$key] ?? $default;

    }

    /**
     * POST
     */
    public static function post(
        string $key,
        mixed $default = null
    ): mixed {

        return $_POST[$key] ?? $default;

    }

    /**
     * REQUEST
     */
    public static function input(
        string $key,
        mixed $default = null
    ): mixed {

        return $_REQUEST[$key] ?? $default;

    }

    /**
     * FILE
     */
    public static function file(
        string $key
    ): ?array {

        return $_FILES[$key] ?? null;

    }

    /**
     * SESSION
     */
    public static function session(
        string $key,
        mixed $default = null
    ): mixed {

        return $_SESSION[$key] ?? $default;

    }

    /**
     * HAS
     */
    public static function has(
        string $key
    ): bool {

        return isset($_REQUEST[$key]);

    }

    /**
     * ALL POST
     */
    public static function all(): array
    {
        return $_POST;
    }

    /**
     * ALL FILES
     */
    public static function files(): array
    {
        return $_FILES;
    }

    /**
     * METHOD
     */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * AJAX
     */
    public static function isAjax(): bool
    {
        return strtolower(
            $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''
        ) === 'xmlhttprequest';
    }
}