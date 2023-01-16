<?php

declare(strict_types=1);

namespace App\Service\Util;

use Symfony\Component\HttpFoundation\Request;

/**
 * Base service with utilization methods.
 */
class BinUtilService
{
    public static function logToTest(mixed $data, string $mode = 'w'): void
    {
        $path = self::getRootPath() . '/var/test';
        $file = fopen($path, $mode);
        if ($file) {
            fwrite($file, (json_encode($data, JSON_PRETTY_PRINT) ?: '') . PHP_EOL);
            fclose($file);
        }
    }

    public function saveLastErrorTrace(\Throwable $e, ?Request $request = null): void
    {
        $httpUtilService = new HttpUtilService($this);
        $path = $this->getRootPath() . '/var/last_error_trace';
        $file = fopen($path, 'w');
        if ($file) {
            if ($request) {
                $content = json_decode($request->getContent(), true);
                fwrite($file, (json_encode($content, JSON_PRETTY_PRINT) ?: '') . PHP_EOL);
            }
            if ($httpUtilService->hasErrors()) {
                fwrite($file, json_encode($httpUtilService->getErrors(), JSON_PRETTY_PRINT) . PHP_EOL);
            }
            fwrite($file, $e->getMessage() . ' => ' . $e->getFile() . ' => ' . $e->getLine() . PHP_EOL);
            fwrite($file, json_encode($e->getTrace(), JSON_PRETTY_PRINT) ?: '');
            fclose($file);
        }
    }

    public static function getRootPath(): string
    {
        return dirname(dirname(dirname(__DIR__)));
    }

    public function isDev(): bool
    {
        $env = ['dev', 'staging'];

        return in_array($_ENV['APP_ENV'], $env, true);
    }

    public function isProduction(): bool
    {
        return 'prod' === $_ENV['APP_ENV'];
    }

    public function generateToken(int $length = 8): string
    {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }

    public function normalizeName(string $name): string
    {
        return preg_replace('/\s+/', '_', preg_replace(
            '/[^\p{L}\p{N}]/u',
            ' ',
            $name
        ) ?? '') ?? '';
    }
}
