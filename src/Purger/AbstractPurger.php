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

        /** @var Connection $connection */
        $connection = $this->em->getConnection();

        /** @var AbstractPlatform $dbPlatform */
        $dbPlatform = $connection->getDatabasePlatform();

        $connection->query('SET FOREIGN_KEY_CHECKS=0');
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $connection->executeStatement($q);
        $connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
