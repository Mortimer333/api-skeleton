<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

namespace App\Tests\Unit;

use App\Tests\UnitTester;
use Codeception\Test\Unit;

class BaseUnitAbstract extends Unit
{
    protected UnitTester $tester;

    protected function getService(string $class) // @phpstan-ignore-line
    {
        $service = $this->tester->getService($class);

        if (!$service) {
            throw new \Exception($class . " doesn't exist as a service");
        }

        return $service;
    }

    public function _before(): void
    {
    }

    public function _after(): void
    {
    }
}
