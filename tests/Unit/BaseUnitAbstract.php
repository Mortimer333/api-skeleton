<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

namespace App\Tests\Unit;

use App\Tests\UnitTester;
use Codeception\Test\Unit;

class BaseUnitAbstract extends Unit
{
    protected UnitTester $tester;

    public function _before(): void
    {
    }

    public function _after(): void
    {
    }
}
