<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Util\BinUtilService;
use App\Service\Util\HttpUtilService;
use Jose\Component\Core\JWKSet;
use Psr\Cache\CacheItemPoolInterface;

abstract class JWTServiceAbstract
{
    public const AUDIENCE = 'Users';
    public const ISSUER = 'Board Meister Internal';

    protected CacheItemPoolInterface $cache;
    protected BinUtilService $baseUtilService;
    protected HttpUtilService $httpUtilService;

    /**
     * @return array<JWKSet>
     */
    public function getKeys(): array
    {
        if (!isset($_ENV['JWT_KEYS_ENCRYPTION']) || !isset($_ENV['JWT_KEYS_SIGNATURE'])) {
            throw new \InvalidArgumentException('JWT keys are not set in environment', 500);
        }
        $signatureKeySet = JWKSet::createFromJson($_ENV['JWT_KEYS_SIGNATURE']);
        $encryptionKeySet = JWKSet::createFromJson($_ENV['JWT_KEYS_ENCRYPTION']);

        if (!$signatureKeySet->has('sig-main') || !$encryptionKeySet->has('enc-main')) {
            throw new \InvalidArgumentException("JWT keys don't have required IDs set", 500);
        }

        return [
            $signatureKeySet,
            $encryptionKeySet,
        ];
    }

    protected function getLastJTIKey(): string
    {
        return 'jwt-jti';
    }

    /**
     * @param array<mixed> $header
     *
     * @return array<mixed>
     */
    public function addRequiredToHeader(int $userId, array $header): array
    {
        return array_merge([
            'alg' => $header['alg'] ?? throw new \Exception('Missing algorithm header in token', 500),
            'jti' => $header['jti'] ?? $this->createJTI($userId),
            'iss' => $header['iss'] ?? self::ISSUER,
            'aud' => $header['aud'] ?? self::AUDIENCE,
            'iat' => $header['iat'] ?? time(),
            'nbf' => $header['nbf'] ?? time(),
            'exp' => $header['exp'] ?? time() + $this->httpUtilService->getTokenExpTimeSeconds(),
        ], $header);
    }

    public function createJTI(int $userId): string
    {
        return 'api_' . $this->baseUtilService->generateUniqueToken((string) $userId);
    }

    public function validateAlgorithmEnvsExist(): void
    {
        if (
            !isset($_ENV['JWT_SINGNATURE_ALGORITHM'])
            || !isset($_ENV['JWT_ENCRYTPION_ALGORITHM'])
            || !isset($_ENV['JWT_CONTENT_ENCRYTPION_ALGORITHM'])
        ) {
            throw new \InvalidArgumentException('JWT algorithms are not set in environment', 500);
        }
    }
}
