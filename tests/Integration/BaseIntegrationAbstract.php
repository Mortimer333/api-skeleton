<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\IntegrationTester;
use Codeception\Test\Unit;

class BaseIntegrationAbstract extends Unit
{
    protected IntegrationTester $tester;

    /** @var array<int, object> $toRemove */
    protected array $toRemove = [];

    protected function getService(string $class) // @phpstan-ignore-line
    {
        $service = $this->tester->getService($class);

        if (!$service) {
            throw new \Exception($class . " doesn't exist as a service");
        }

        return $service;
    }

    protected function addToRemove(object $entity): void
    {
        $this->toRemove[] = $entity;
    }

    public function _before(): void
    {
    }

    public function _after(): void
    {
        $this->tester->removeSavedEntities($this->toRemove);
    }
}
