<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\Fixtures;

abstract class BaseCestAbstract
{
    /** @var array<int, object> $toRemove */
    protected array $toRemove = [];

    public function _before(ApiTester $I): void
    {
    }

    public function _after(ApiTester $I): void
    {
        $I->removeSavedEntities($this->toRemove);
    }

    protected function addToRemove(object $entity): void
    {
        $this->toRemove[] = $entity;
    }
}
