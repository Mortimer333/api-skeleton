<?php

namespace App\Purger;

use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

class TestPurgerFactory implements PurgerFactory
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createForEntityManager(
        ?string $emName,
        EntityManagerInterface $em,
        array $excluded = [],
        bool $purgeWithTruncate = false
    ): PurgerInterface {
        return new TestPurger();
    }
}
