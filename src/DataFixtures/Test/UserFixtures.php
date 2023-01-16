<?php

declare(strict_types=1);

namespace App\DataFixtures\Test;

use App\DataFixtures\TestFixturesAbstract;
use App\Entity\User;
use App\Service\Helper\TestHelper;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends TestFixturesAbstract
{
    public function __construct(
        protected UserPasswordHasherInterface $hasher
    ) {
    }

    public static function getGroups(): array
    {
        return array_merge(['admin'], parent::getGroups());
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail(TestHelper::USER_EMAIL)
            ->setFirstname('userTest')
            ->setSurname('userTest')
            ->setRoles(['ROLE_USER'])
        ;
        $this->setHashedPassword($admin, TestHelper::USER_PLAIN_PASSWORD);
        $manager->persist($admin);

        $superUser = (new User())
            ->setEmail(TestHelper::SUPER_USER_EMAIL)
            ->setFirstname('superUserTest')
            ->setSurname('superUserTest')
            ->setRoles(['ROLE_SUPER_USER'])
            ->setIsSuper(true)
        ;
        $this->setHashedPassword($superUser, TestHelper::SUPER_USER_PLAIN_PASSWORD);
        $manager->persist($superUser);

        $manager->flush();
    }

    protected function setHashedPassword(User $admin, string $plainPassword): void
    {
        $hashedPassword = $this->hasher->hashPassword(
            $admin,
            $plainPassword
        );
        $admin->setPassword($hashedPassword);
    }
}
