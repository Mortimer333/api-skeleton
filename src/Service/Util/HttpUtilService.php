<?php

declare(strict_types=1);

namespace App\Service\Util;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Base service with http utilization methods.
 */
class HttpUtilService
{
    public const TOKEN_EXP_TIME_SECONDS = 60 * 60; // One hour
    public const TOKEN_EXP_TIME_DEV_SECONDS = 24 * 60 * 60; // One day

    /** @var array<mixed> $errors */
    protected static array $errors = [];
    protected static ?int $limit = null;
    protected static ?int $offset = null;
    protected static ?int $total = null;

    public function __construct(
        protected BinUtilService $binUtilService,
    ) {
    }

    public static function setTotal(?int $total): void
    {
        self::$total = $total;
    }

    public static function getTotal(): ?int
    {
        return self::$total;
    }

    public static function setLimit(?int $limit): void
    {
        self::$limit = $limit;
    }

    public static function getLimit(): ?int
    {
        return self::$limit;
    }

    public static function setOffset(?int $offset): void
    {
        self::$offset = $offset;
    }

    public static function getOffset(): ?int
    {
        return self::$offset;
    }

    /**
     * @param array<string>|null $path
     */
    public static function addError(string $error, ?array $path = null): void
    {
        if (null === $path) {
            self::$errors[] = $error;

            return;
        }

        self::createErrorPath($error, $path, self::$errors);
    }

    /**
     * @param array<string> $path
     * @param array<mixed>  $errors
     */
    protected static function createErrorPath(string $error, array $path, array &$errors, int $caret = 0): void
    {
        if (!isset($path[$caret])) {
            $errors[] = $error;

            return;
        }

        if (!isset($errors[$path[$caret]])) {
            $errors[$path[$caret]] = [];
        }

        if (is_string($errors[$path[$caret]])) {
            return;
        }

        self::createErrorPath($error, $path, $errors[$path[$caret]], $caret + 1);
    }

    /**
     * @param array<string> $errors
     */
    public static function setErrors(array $errors): void
    {
        self::$errors = $errors;
    }

    /**
     * @return array<mixed>
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    public static function hasErrors(): bool
    {
        return count(self::$errors) > 0;
    }

    public static function clearErrors(): void
    {
        self::$errors = [];
    }

    public function getTokenExpTimeSeconds(): int
    {
        if ($this->binUtilService->isDev()) {
            return static::TOKEN_EXP_TIME_DEV_SECONDS;
        }

        return static::TOKEN_EXP_TIME_SECONDS;
    }

    /**
     * @param array<mixed>  $data
     * @param array<string> $errors
     * @param array<mixed>  $headers
     */
    public function jsonResponse(
        string $message,
        int $status = 200,
        bool $success = true,
        array $data = [],
        ?int $offset = null,
        ?int $limit = null,
        ?int $total = null,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse(
            [
                'message' => $message,
                'status' => $status,
                'success' => $success,
                'data' => $data,
                'offset' => $offset ?? self::$offset,
                'limit' => $limit ?? self::$limit,
                'total' => $total ?? self::$total,
                'errors' => $errors,
            ],
            $status,
            $headers
        );
    }

    public function validateHttpStatus(int $status): bool
    {
        // From https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        $statuses = [
            100 => true, 101 => true, 102 => true, 103 => true,

            200 => true, 201 => true, 202 => true, 203 => true, 204 => true, 205 => true, 206 => true,
            207 => true, 208 => true, 226 => true,

            300 => true, 301 => true, 302 => true, 303 => true, 304 => true, 305 => true, 306 => true,
            307 => true, 308 => true,

            400 => true, 401 => true, 402 => true, 403 => true, 404 => true, 405 => true, 406 => true,
            407 => true, 408 => true, 409 => true, 410 => true, 411 => true, 412 => true, 413 => true,
            414 => true, 415 => true, 416 => true, 417 => true, 418 => true, 421 => true, 422 => true,
            423 => true, 424 => true, 425 => true, 426 => true, 428 => true, 429 => true, 431 => true,
            451 => true,
            // nginx
            444 => true,  494 => true, 495 => true, 496 => true, 497 => true, 499 => true,

            500 => true, 501 => true, 502 => true, 503 => true, 504 => true, 505 => true, 506 => true,
            507 => true, 508 => true, 510 => true, 511 => true,
        ];

        return $statuses[$status] ?? false;
    }

    public function getStatusCode(\Throwable $exception): int
    {
        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return self::validateHttpStatus($exception->getCode())
            ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function getProperResponseFromException(\Throwable $exception): JsonResponse
    {
        return self::jsonResponse(
            message: $exception->getMessage() ?: 'No message',
            status: $this->getStatusCode($exception),
            success: false,
            errors: self::getErrors(),
        );
    }

    /**
     * @return array<mixed>
     */
    public function getBody(Request $request): array
    {
        if ('GET' === $request->getMethod()) {
            parse_str($request->getQueryString() ?: '', $body);
            foreach ($body as $i => $item) {
                $body[$i] = $item = is_array($item) ? $item : json_decode($item, true);
                // Fix for Swagger double encoding query arrays
                if (is_array($item)) {
                    foreach ($item as $k => $subItem) {
                        if (!is_string($subItem)) {
                            continue;
                        }
                        $item[$k] = json_decode($subItem, true);
                    }
                    $body[$i] = $item;
                }
            }
        } else {
            /** @var string $content */
            $content = $request->getContent();
            $body = json_decode($content, true);
        }
        /** @var array<string>|null $body */
        if (is_null($body)) {
            throw new \InvalidArgumentException('Body is not a valid JSON', 400);
        }

        return $body;
    }
}
