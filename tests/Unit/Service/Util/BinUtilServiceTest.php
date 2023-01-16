<?php

namespace App\Tests\Unit\Service\Util;

use App\Service\Util\BinUtilService;
use App\Tests\Unit\BaseUnitAbstract;
use Codeception\Attribute\Depends;

class BinUtilServiceTest extends BaseUnitAbstract
{
    protected BinUtilService $binUtilService;

    public function _before(): void
    {
        parent::_before();
        $this->binUtilService = new BinUtilService();
    }

    public function _after(): void
    {
        parent::_after();
        $this->binUtilService->logToTest('');
    }

    public function testGetRootPath(): void
    {
        $root = $this->binUtilService->getRootPath();
        $realRoot = dirname(dirname(dirname(dirname(__DIR__))));
        $this->assertEquals($root, $realRoot, 'Proper root path is created');
    }

    public function testNormalizeName(): void
    {
        $name = $this->binUtilService->normalizeName('test !@#$%^&*()_+[]{}\|\'";:/?.,><~` 1234567890');
        $this->assertEquals('test_1234567890', $name);
    }

    #[Depends('testGetRootPath')]
    public function testLogToTestOverwrite(): void
    {
        $testPath = $this->binUtilService->getRootPath() . '/var/test';
        if (is_file($testPath)) {
            unlink($testPath);
        }
        $this->assertFalse(is_file($testPath), 'Test file was removed');
        $testRealContent = "\"test\"\n";
        $this->binUtilService->logToTest('test');
        $this->assertTrue(is_file($testPath), 'Test file was created');
        $testContent = file_get_contents($testPath);
        $this->assertEquals($testRealContent, $testContent, 'Test file has proper content');
    }

    #[Depends('testGetRootPath')]
    public function testLogToTestAppend(): void
    {
        $testPath = $this->binUtilService->getRootPath() . '/var/test';
        if (is_file($testPath)) {
            unlink($testPath);
        }
        $this->assertFalse(is_file($testPath), 'Test file was removed');
        $testRealContent = "\"test\"\n";
        $this->binUtilService->logToTest('test', 'a');
        $this->binUtilService->logToTest('test', 'a');
        $this->assertTrue(is_file($testPath), 'Test file was created');
        $testContent = file_get_contents($testPath);
        $this->assertEquals($testRealContent . $testRealContent, $testContent, 'Test file has proper content');
    }

    #[Depends('testGetRootPath')]
    public function testSaveLastErrorTrace(): void
    {
        $lastErrorPath = $this->binUtilService->getRootPath() . '/var/last_error_trace';
        if (is_file($lastErrorPath)) {
            unlink($lastErrorPath);
        }
        $this->assertFalse(is_file($lastErrorPath), 'Last error file was removed');
        $exception = new \Exception();
        $this->binUtilService->saveLastErrorTrace($exception);
        $this->assertTrue(is_file($lastErrorPath), 'Last error was created');
        $testContent = file_get_contents($lastErrorPath);
        $this->assertFalse(empty($testContent), 'Last error is not empty');
    }
}
