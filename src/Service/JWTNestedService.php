<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\UnauthorizedException;
use App\Service\Util\BinUtilService;
use App\Service\Util\HttpUtilService;
use Jose\Component\NestedToken\NestedTokenBuilder;
use Jose\Component\NestedToken\NestedTokenLoader;
use Jose\Component\Signature\JWS;
use Psr\Cache\CacheItemPoolInterface;

class JWTNestedService extends JWTServiceAbstract
{
    public function __construct(
        protected NestedTokenBuilder $nestedTokenBuilder,
        protected NestedTokenLoader $nestedTokenLoader,
        protected CacheItemPoolInterface $cache,
        protected BinUtilService $baseUtilService,
        protected HttpUtilService $httpUtilService,
    ) {
        $this->validateAlgorithmEnvsExist();
    }

    /**
     * Nested token is signed and have encrypted content.
     *
     * @param array<mixed> $payload
     */
    public function createToken(array $payload): string
    {
        [$signatureKeySet, $encryptionKeySet] = $this->getKeys();

        $sigJWK = $signatureKeySet->get('sig-main');
        $encJWK = $encryptionKeySet->get('enc-main');

        $payload = json_encode($payload);
        if (!$payload) {
            throw new \InvalidArgumentException('Token payload was improperly formated', 500);
        }

        // https://web-token.spomky-labs.com/advanced-topics-1/nested-tokens
        $token = $this->nestedTokenBuilder->create(
            $payload,
            [[
                'key' => $sigJWK,
                'protected_header' => $this->addRequiredToHeader(['alg' => $_ENV['JWT_SINGNATURE_ALGORITHM']]),
            ]],
            'jws_compact',
            ['alg' => $_ENV['JWT_ENCRYTPION_ALGORITHM'], 'enc' => $_ENV['JWT_CONTENT_ENCRYTPION_ALGORITHM']],
            [],
            [[
                'key' => $encJWK,
            ]],
            'jwe_compact'
        );

        // Decryption of nested token takes about 2s, saving assigned payload to cache for quicker response on read,
        // also this works as `exp` becuase `expiries` checker for nested doesn't work
        $cachedNestedTokenPayload = $this->cache->getItem($this->getNestedTokenCacheKey($token))
                    ->set($payload)
                    ->expiresAfter($this->httpUtilService->getTokenExpTimeSeconds())
        ;
        $this->cache->save($cachedNestedTokenPayload);

        return $token;
    }

    /**
     * @return array<mixed>
     */
    public function getTokenPayload(string $token, bool $cached = true): array
    {
        if ($cached) {
            $cachedToken = $this->cache->getItem($this->getNestedTokenCacheKey($token));
            if (!$cachedToken->isHit()) {
                throw new UnauthorizedException("Token doesn't figure in our Database or expired", 401);
            }

            $payload = $cachedToken->get();
        } else {
            $jws = $this->loadAndVerifyToken($token);
            $payload = $jws->getPayload();
        }

        $payload = json_decode($payload, true);

        if (!is_array($payload)) {
            throw new UnauthorizedException("Token payload doesn't have correct type", 401);
        }

        return $payload;
    }

    public function loadAndVerifyToken(string $token): JWS
    {
        [$signatureKeySet, $encryptionKeySet] = $this->getKeys();
        $jws = $this->nestedTokenLoader->load($token, $encryptionKeySet, $signatureKeySet);

        return $jws;
    }

    public function getNestedTokenCacheKey(string $token): string
    {
        return 'nested-token-' . $token;
    }
}
