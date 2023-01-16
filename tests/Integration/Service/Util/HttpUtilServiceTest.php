<?php

namespace App\Tests\Integration\Service\Util;

use App\Service\Util\BinUtilService;
use App\Service\Util\HttpUtilService;
use App\Tests\Integration\BaseIntegrationAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;

class HttpUtilServiceTest extends BaseIntegrationAbstract
{
    protected HttpUtilService $httpUtilService;

    public function setUp(): void
    {
        parent::setUp();
        $this->httpUtilService = new HttpUtilService($this->getService(BinUtilService::class));
    }

    public function testJsonResponseDefaults(): void
    {
        $response = $this->httpUtilService->jsonResponse('test');
        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(
            [
                'message' => 'test',
                'status' => 200,
                'success' => true,
                'data' => [],
                'offset' => null,
                'limit' => null,
                'total' => null,
                'errors' => [],
            ],
            json_decode($response->getContent() ?: '', true)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testJsonResponseCustom(): void
    {
        $custom = [
            'message' => 'test',
            'status' => 401,
            'success' => false,
            'data' => ['test'],
            'offset' => 2,
            'limit' => 3,
            'total' => 5,
            'errors' => ['test error'],
        ];
        $response = $this->httpUtilService->jsonResponse(...$custom);
        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(
            $custom,
            json_decode($response->getContent() ?: '', true)
        );
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testExceptionIsProperlyTransformedToResponse(): void
    {
        $e = new \Exception('test', 400);
        $response = $this->httpUtilService->getProperResponseFromException($e);
        $this->assertEquals(400, $response->getStatusCode());
        $content = json_decode($response->getContent() ?: '', true);
        $this->assertEquals('test', $content['message']);
        $this->assertFalse($content['success']);
    }
}
