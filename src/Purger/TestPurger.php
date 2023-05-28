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
        $transactionWasActive = false;
        if ($connection->isTransactionActive()) {
            $transactionWasActive = true;
            $connection->commit();
        }

        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $entity) {
            $cmd = $this->em->getClassMetadata($entity->getName());

            // Clear ManyToMany tables
            foreach ($cmd->getAssociationMappings() as $associationMapping) {
                if ($associationMapping['joinTable']['name'] ?? false) {
                    $this->truncateTable($associationMapping['joinTable']['name']);
                }
            }

            $this->truncate($entity->getName());
        }

        if ($transactionWasActive) {
            $connection->beginTransaction();
        }
    }
}
