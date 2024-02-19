<?php

namespace App\Tests\Unit\Service\Util;

use App\Service\Util\BinUtilService;
use App\Service\Util\HttpUtilService;
use App\Tests\Unit\BaseUnitAbstract;
use Codeception\Attribute\Examples;
use Symfony\Component\HttpFoundation\Request;

class HttpUtilServiceTest extends BaseUnitAbstract
{
    protected HttpUtilService $httpUtilService;

    public function _before(): void
    {
        parent::_before();
        $this->httpUtilService = new HttpUtilService(
            $this->createMock(BinUtilService::class)
        );
    }

    #[Examples(500, true)]
    #[Examples(404, true)]
    #[Examples(401, true)]
    #[Examples(300, true)]
    #[Examples(200, true)]
    #[Examples(201, true)]
    #[Examples(100, true)]
    #[Examples(10, false)]
    #[Examples(1, false)]
    #[Examples(0, false)]
    #[Examples(-1, false)]
    #[Examples(1000, false)]
    public function testValidateHttpStatus(int $status, bool $expectedResult): void
    {
        $result = $this->httpUtilService->validateHttpStatus($status);
        $this->assertEquals($expectedResult, $result, 'Status was inncorect: ' . $status);
    }

    public function testErrorFunctionality(): void
    {
        $this->httpUtilService->setErrors([]);
        $this->assertEquals(0, count($this->httpUtilService->getErrors()));
        $this->httpUtilService->setErrors(['error', 'error2']);
        $this->assertEquals(2, count($this->httpUtilService->getErrors()));
        $this->httpUtilService->addError('error3');
        $this->assertEquals(3, count($this->httpUtilService->getErrors()));
        $this->assertEquals(['error', 'error2', 'error3'], $this->httpUtilService->getErrors());
        $this->assertTrue($this->httpUtilService->hasErrors());
        $this->httpUtilService->setErrors([]);
        $this->assertFalse($this->httpUtilService->hasErrors());
    }

    public function testGetTokenTimeForDev(): void
    {
        $binUtilService = $this->createMock(BinUtilService::class);
        $binUtilService->method('isDev')->willReturn(true);
        $this->httpUtilService = new HttpUtilService($binUtilService);
        $this->assertEquals(
            HttpUtilService::TOKEN_EXP_TIME_DEV_SECONDS,
            $this->httpUtilService->getTokenExpTimeSeconds()
        );
    }

    public function testGetTokenTimeForOtherEnv(): void
    {
        $binUtilService = $this->createMock(BinUtilService::class);
        $binUtilService->method('isDev')->willReturn(false);
        $this->httpUtilService = new HttpUtilService($binUtilService);
        $this->assertEquals(
            HttpUtilService::TOKEN_EXP_TIME_SECONDS,
            $this->httpUtilService->getTokenExpTimeSeconds()
        );
    }

    public function testBodyIsProperlyConverted(): void
    {
        $request = $this->createMock(Request::class);
        $content = ['key' => 'value'];
        $request->method('getContent')->willReturn(json_encode($content));
        $this->assertEquals($content, $this->httpUtilService->getBody($request));
    }

    public function testExceptionIsThrownWhenBodyNotJson(): void
    {
        $request = $this->createMock(Request::class);
        $content = '[}key';
        $request->method('getContent')->willReturn($content);
        $this->expectException(\InvalidArgumentException::class);
        $this->httpUtilService->getBody($request);
    }
}
