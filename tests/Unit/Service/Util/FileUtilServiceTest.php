<?php

namespace App\Tests\Unit\Service\Util;

use App\Service\Util\FileUtilService;
use App\Tests\Unit\BaseUnitAbstract;

class FileUtilServiceTest extends BaseUnitAbstract
{
    protected FileUtilService $fileUtilService;
    protected string $filepath = \ROOT_PATH . '/var/fileutiltest.log'; // @phpstan-ignore-line

    public function _before(): void
    {
        parent::_before();
        $this->fileUtilService = new FileUtilService();
    }

    public function _after(): void
    {
        parent::_after();
        if (is_file($this->filepath)) {
            unlink($this->filepath);
        }
    }

    public function testFileIsProperlyRead(): void
    {
        $file = fopen($this->filepath, 'w');
        if (!$file) {
            throw new \Exception('File not created');
        }
        $fileContent = '';
        for ($i = 0; $i < 10; ++$i) {
            $line = 'line ' . ($i + 1) . "\n";
            fwrite($file, $line);
            $fileContent .= $line;
        }
        $line = 'line 11';
        fwrite($file, 'line 11'); // don't end it with new line
        $fileContent .= $line;
        fclose($file);

        [$content, $cursor] = $this->fileUtilService->readLines($this->filepath, amount: 1);
        $this->assertEquals("line 1\n", $content, 'First line');
        [$content, $cursor] = $this->fileUtilService->readLines($this->filepath, $cursor, 1);
        $this->assertEquals("line 2\n", $content, 'Second line');

        [$content, $cursorFive] = $this->fileUtilService->readLines($this->filepath, amount: 5);
        $this->assertEquals("line 1\nline 2\nline 3\nline 4\nline 5\n", $content, 'Five lines');

        [$content, $cursor] = $this->fileUtilService->readLines($this->filepath, $cursorFive, 2);
        $this->assertEquals("line 6\nline 7\n", $content, 'Five lines');

        [$content, $cursor] = $this->fileUtilService->readLines($this->filepath, amount: -1);
        $this->assertEquals($fileContent, $content, 'Whole file');

        [$content, $cursor] = $this->fileUtilService->readLines($this->filepath, $cursorFive, -1);
        $this->assertEquals("line 6\nline 7\nline 8\nline 9\nline 10\nline 11", $content, 'Half of the file');
    }
}
