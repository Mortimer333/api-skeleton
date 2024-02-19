<?php

declare(strict_types=1);

namespace App\Service\Util;

class FileUtilService
{
    public const FILE_TO_TYPE = [
        'application/json' => 'json',
    ];

    public const IMAGE_MIME_TYPE = [
        'image/png',
        'image/jpg',
        'image/jpeg',
        'image/webp',
    ];

    public const ALLOWED_MIME_TYPE = [
        'image/png' => 'png',
        'image/jpg' => 'jpg',
        'image/jpeg' => 'jpeg',
        'image/webp' => 'webp',
        'application/json' => 'json',
    ];

    public static function extToType(string $mime): string
    {
        if (in_array($mime, self::IMAGE_MIME_TYPE)) {
            return 'image';
        }

        return self::FILE_TO_TYPE[$mime] ?? 'unknown';
    }

    public static function isJson(string $text): bool
    {
        return (bool) json_decode($text);
    }

    /**
     * @param string $path   Absolute path to the file
     * @param int    $cursor Where to start in bytes
     * @param int    $amount Amount of lines to retrieve (pass -1 to read whole file)
     *
     * @return array{0: string, 1: int, 2: int} Returns:
     *                                          - 0 => Read file content
     *                                          - 1 => Position of the cursor (in bytes)
     *                                          - 2 => The amount of read lines
     */
    public static function readLines(string $path, int $cursor = 0, int $amount = 10): array
    {
        $content = '';

        $file = fopen($path, 'r');
        if (!$file) {
            throw new \Exception('File not found', 500);
        }

        fseek($file, $cursor);
        $char = fgetc($file);

        $addedLines = 0;
        $previousChar = '';
        while (false !== $char && ($addedLines < $amount || -1 === $amount)) {
            if ("\n" === $char && "\r" !== $previousChar || "\r" === $char && "\n" !== $previousChar) {
                ++$addedLines;
            }
            $previousChar = $char;
            $content .= $char;
            fseek($file, ++$cursor);
            $char = fgetc($file);
        }

        fclose($file);

        return [$content, $cursor, $addedLines];
    }

    public static function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new \Exception("Passed directory doesn't exist", 500);
        }

        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    public static function humanFilesize(int $bytes, int $decimals = 2): string
    {
        $sizes = 'BKMGTP';
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);
        $size = $sizes[$factor] ?? '-';
        if ('B' != $size && '-' != $size) {
            $size .= 'B';
        }

        return sprintf("%.{$decimals}f", $bytes / pow(1000, $factor)) . ' ' . $size;
    }
}
