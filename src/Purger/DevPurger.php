<?php

declare(strict_types=1);

namespace App\Purger;

use App\Entity\Deploy\DeployStatus;
use Doctrine\DBAL\Connection;

class DevPurger extends AbstractPurger
{
    public function purge(): void
    {
        /** @var Connection $connection */
        $connection = $this->em->getConnection();

        $connection->beginTransaction();

//        $this->truncate([entity]);

        $connection->commit();
        $connection->beginTransaction();
    }
}
