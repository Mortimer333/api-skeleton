<?php

declare(strict_types=1);

namespace App\Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use App\Entity\Deploy\DeployActionRun;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Entity\Api\User;

class Shared extends \Codeception\Module
{
    protected Connection $connection;
    protected EntityManagerInterface $em;
    protected ManagerRegistry $managerRegistry;
    /** @var array<array<string, object|string>> $entities */
    protected array $entities = [];

    public function clearToRemove(): void
    {
        $this->entities = [];
    }

    public function addToRemove(object $entity): void
    {
        $this->entities[] = $entity;
    }

    public function unshiftToRemove(object $entity): void
    {
        array_unshift($this->entities, $entity);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function assertEntityEqualsPayload(object $entity, array $payload, array $skip = []): void
    {
        foreach ($payload as $field => $value) {
            if (in_array($field, $skip)) {
                continue;
            }
            $getter = 'get' . ucfirst($field);
            if (!method_exists($entity, $getter)) {
                $this->fail('Entity ' .  $entity::class . " doesn't have " . $field);
            }

            $valueEn = $entity->$getter();
            if (is_array($value)) {
                if (!$valueEn instanceof Collection) {
                    $this->fail("Entity doesn't have array on field " . $field);
                }

                $ids = [];
                /** @var object $relative */
                foreach ($valueEn as $relative) {
                    $ids[] = $relative->getId();
                }
                sort($value);
                sort($ids);
                $this->assertEquals($value, $ids);
            } else {
                if (is_object($valueEn)) {
                    $valueEn = $valueEn->getId();
                }
                $this->assertEquals($value, $valueEn);
            }
        }
    }

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
            sleep(1); // wait 1s for command to finish
            $this->em->refresh($deployActionRun);
            if ('running' !== $deployActionRun->getStatus()?->getName()) {
                break;
            }
        } while ($run + 180 > time());

        return $deployActionRun;
    }

    /**
     * @template T
     *
     * @param class-string<T> $service
     *
     * @return T
     *
     * @throws \Exception
     */
    public function getService(string $service)
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

    public function setEm(EntityManagerInterface $em, string $emAlias): void
    {
        $this->em = $em;
        $this->getModule('Doctrine2')->em = $em;

        /** @var \Codeception\Module\Symfony $sfModule */
        $sfModule = $this->getModule('Symfony');
        // Container can change between tests
        /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
        $container = $sfModule->_getContainer();

        $service = 'doctrine.orm.' . $emAlias . '_entity_manager';
        if ($container->initialized($service)) {
            return;
        }

        $container->set($service, $em);
        $sfModule->persistService($service);
    }

    public function changeEm(string $emAlias): void
    {
        if ('internal' !== $emAlias && 'api' !== $emAlias) {
            throw new \Error('Unknown connection: ' . $emAlias);
        }

        $doctrine = $this->getService(ManagerRegistry::class);
        $em = $doctrine->getManager($emAlias);
        if (!$em->isOpen()) {
            $doctrine->resetManager($emAlias);
        }
        $this->setEm($em, $emAlias);
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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function removeSavedEntities(): void
    {
        $em = $this->getEm();
        if (!$em->isOpen()) {
            $this->getManagerRegistry()->resetManager(); // Have to reset entity on exception
            $this->connection = $em->getConnection();
        }

        $transaction = $this->getConnection()->isTransactionActive();
        if ($transaction) {
            $this->getConnection()->commit();
        }

        $doctrine = $this->getManagerRegistry();
        foreach ($this->entities as $entity) {
            if (!$em->isOpen()) {
                $doctrine->resetManager();
            }

            if (!$entity->getId()) {
                continue;
            }

            $entity = $em->getRepository($entity::class)->find($entity->getId()); // @phpstan-ignore-line
            if (!$entity) {
                continue;
            }

            $em->remove($entity);
            if ($entity instanceof User && $entity->getData()) {
                $em->remove($entity->getData());
            }
        }
        $em->flush();

        if ($transaction) {
            $this->getConnection()->beginTransaction();
        }
    }
}
