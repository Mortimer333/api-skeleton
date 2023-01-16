<?php

namespace App\Checker;

use Jose\Component\Checker\HeaderChecker;
use Jose\Component\Checker\InvalidHeaderException;
use Psr\Cache\CacheItemPoolInterface;

final class JTIChecker implements HeaderChecker
{
    public function __construct(
        protected CacheItemPoolInterface $cache,
    ) {
    }

    public function checkHeader(mixed $value): void
    {
        $jtiItem = $this->cache->getItem($value);
        if (!$jtiItem->isHit()) {
            throw new InvalidHeaderException('JTI header expired', 'jti', $value);
        }
    }

    // This header parameter name.
    public function supportedHeader(): string
    {
        return 'jti';
    }

    // This method indicates if this parameter must be in the protected header or not.
    public function protectedHeaderOnly(): bool
    {
        return true;
    }
}
