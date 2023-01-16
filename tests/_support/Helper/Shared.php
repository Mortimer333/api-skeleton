<?php

declare(strict_types=1);

namespace App\Tests\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use App\Entity\Deploy\DeployActionRun;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class Shared extends \Codeception\Module
{
    protected Connection $connection;
    protected EntityManagerInterface $em;
    protected ManagerRegistry $managerRegistry;

    /*
     * Define custom actions here
     */
    public function waitForCommandToFinish(DeployActionRun $deployActionRun): DeployActionRun
    {
        if (!isset($this->em)) {
            $this->em = $this->getService(EntityManagerInterface::class);
        }

        $run = time();
        do {
            usleep(50000); // wait .05s for command to finish
            $this->em->refresh($deployActionRun);
            if ('running' !== $deployActionRun->getStatus()?->getName()) {
                break;
            }
        } while ($run + 2 > time());

        return $deployActionRun;
    }

    public function getService(string $service): object
    {
        return $this->getModule('Symfony')->_getContainer()->get($service);
    }

    public function getConnection(): Connection
    {
        if (isset($this->connection)) {
            return $this->connection;
        }

        $this->connection = $this->getService(Connection::class);

        return $this->connection;
    }

    public function getEm(): EntityManagerInterface
    {
        if (isset($this->em)) {
            return $this->em;
        }

        $this->em = $this->getService(EntityManagerInterface::class);

        return $this->em;
    }

    public function getManagerRegistry(): ManagerRegistry
    {
        if (isset($this->managerRegistry)) {
            return $this->managerRegistry;
        }

        $this->managerRegistry = $this->getService(ManagerRegistry::class);

        return $this->managerRegistry;
    }

    public function removeSavedEntities(array $entities): void
    {
        if (!$this->getEm()->isOpen()) {
            $this->getManagerRegistry()->resetManager(); // Have to reset entity on exception
            $this->connection = $this->getEm()->getConnection();
        }

        $transaction = $this->getConnection()->isTransactionActive();
        if ($transaction) {
            $this->getConnection()->commit();
        }

        foreach ($entities as $entity) {
            $entity = $this->getEm()->getRepository($entity::class)->find($entity->getId()); // @phpstan-ignore-line
            if ($entity) {
                $this->getEm()->remove($entity);
                $this->getEm()->flush();
            }
        }

        if ($transaction) {
            $this->getConnection()->beginTransaction();
        }
    }
}
