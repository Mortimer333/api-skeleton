<?php

declare(strict_types=1);

namespace App\Purger;

use Doctrine\DBAL\Connection;

class TestPurger extends AbstractPurger
{
    public function purge(): void
    {
        /** @var Connection $connection */
        $connection = $this->em->getConnection();

        $connection->beginTransaction();

        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $entity) {
            $this->truncate($entity->getName());
        }

        $connection->commit();
        $connection->beginTransaction();
    }
}
