<?php

declare(strict_types=1);

namespace App\Purger;

use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

abstract class AbstractPurger implements ORMPurgerInterface
{
    public const SKIP = [];
    protected EntityManagerInterface $em;

    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
    }

    /**
     * @param class-string<object> $className
     */
    protected function truncate(string $className): void
    {
        /** @var ClassMetadata $cmd */
        $cmd = $this->em->getClassMetadata($className);

        $this->truncateTable($cmd->getTableName());
    }

    protected function truncateTable(string $table): void
    {
        if (in_array($table, self::SKIP)) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->em->getConnection();

        /** @var AbstractPlatform $dbPlatform */
        $dbPlatform = $connection->getDatabasePlatform();

        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $q = $dbPlatform->getTruncateTableSql($table);
        $connection->executeStatement($q);
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
