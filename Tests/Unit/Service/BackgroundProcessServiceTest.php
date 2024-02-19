<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\BackgroundProcessService;
use App\Tests\Unit\BaseUnitAbstract;

class BackgroundProcessServiceTest extends BaseUnitAbstract
{
    public function testTransformArguments(): void
    {
        $backgroundProcessService = $this->getService(BackgroundProcessService::class);

        $arguments = [
          's' => 'short',
          'long' => 2,
          'empty' => '',
        ];

        $argumentsString = $backgroundProcessService->transformArguments($arguments);
        $this->assertEquals(' -sshort --long 2 --empty', $argumentsString);
    }
}
