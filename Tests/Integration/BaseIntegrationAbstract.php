<?php

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\Util\BinUtilService;
use App\Tests\Support\IntegrationTester;
use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BaseIntegrationAbstract extends Unit
{
    protected IntegrationTester $tester;
    protected Connection $connection;
    protected EntityManagerInterface $em;
    protected ManagerRegistry $managerRegistry;
    /** @var array<int, object> $toRemove */
    protected array $toRemove = [];

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws \Exception
     */
    protected function getService(string $class)
    {
        $service = $this->tester->getService($class);

        if (!$service) {
            throw new \Exception($class . " doesn't exist as a service");
        }

        return $service;
    }

    public function _after(): void
    {
        $this->tester->removeSavedEntities();
    }

    protected function requireTestData(string $path): mixed
    {
        return require BinUtilService::getRootPath() . '/Tests/_data/' . $path;
    }

    protected function requireSharedTestData(string $path): mixed
    {
        return require BinUtilService::getRootPath() . '/shared/Tests/_data/' . $path;
    }

    protected function generateUploadedFile(string $file): UploadedFile
    {
        copy(
            BinUtilService::getRootPath() . '/shared/Tests/_data/' . $file,
            BinUtilService::getRootPath() . '/shared/Tests/_data/dist/' . $file,
        );

        return new UploadedFile(
            BinUtilService::getRootPath() . '/shared/Tests/_data/dist/' . $file,
            'test',
            test: true,
        );
    }
}
