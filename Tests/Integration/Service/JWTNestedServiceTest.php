<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Service\JWTNestedService;
use App\Service\Util\BinUtilService;
use App\Service\Util\HttpUtilService;
use App\Tests\Integration\BaseIntegrationAbstract;
use Jose\Component\NestedToken\NestedTokenBuilder;
use Jose\Component\NestedToken\NestedTokenLoader;
use Psr\Cache\CacheItemPoolInterface;

class JWTNestedServiceTest extends BaseIntegrationAbstract
{
    public function testCreateAndReadNestedToken(): void
    {
        $userId = (int) floor(rand() * 100);
        $jwtNestedService = $this->getJWTNestedService();
        /** @var string $token */
        $token = $jwtNestedService->createToken(['user_id' => $userId]);

        $cache = $this->getService(CacheItemPoolInterface::class);

        $cachedToken = $cache->getItem($jwtNestedService->getNestedTokenCacheKey($token));
        $this->assertTrue($cachedToken->isHit());

        $jws = $jwtNestedService->loadAndVerifyToken($token);

        /** @var string $payload */
        $payload = $jws->getPayload();
        $payload = json_decode($payload, true);
        $this->assertArrayHasKey('user_id', $payload);

        // Quick function to get payload (with cache disabled)
        $payload = $jwtNestedService->getTokenPayload($token, false);
        $this->assertArrayHasKey('user_id', $payload);

        // Cached token
        $payload = $jwtNestedService->getTokenPayload($token);
        $this->assertArrayHasKey('user_id', $payload);
    }

    protected function getJWTNestedService(): JWTNestedService
    {
        return new JWTNestedService(
            $this->getService(NestedTokenBuilder::class),
            $this->getService(NestedTokenLoader::class),
            $this->getService(CacheItemPoolInterface::class),
            $this->getService(BinUtilService::class),
            $this->getService(HttpUtilService::class),
        );
    }
}
