<?php

declare(strict_types=1);

namespace App\Service\Util;

class FileUtilService
{
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
    public function readLines(string $path, int $cursor = 0, int $amount = 10): array
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

    public function deleteDir(string $dir): void
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
}
