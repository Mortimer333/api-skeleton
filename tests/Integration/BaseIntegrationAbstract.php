<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\IntegrationTester;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class BaseIntegrationAbstract extends Unit
{
    protected IntegrationTester $tester;

    /** @var array<int, object> $toRemove */
    protected array $toRemove = [];

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
