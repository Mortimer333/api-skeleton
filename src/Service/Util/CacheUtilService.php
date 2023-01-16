<?php

declare(strict_types=1);

namespace App\Service\Util;

class CacheUtilService
{
    public function generateKey(string $uniqueName, ?string $prefix = null): string
    {
        $base64UniqueName = \base64_encode($uniqueName);

        return $prefix ? $prefix . $base64UniqueName : $base64UniqueName;
    }
}
