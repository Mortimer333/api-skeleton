<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManageService
{
    public function __construct(
        protected UserPasswordHasherInterface $passwordHasher,
        protected EntityManagerInterface $em,
        protected UserService $userService,
    ) {
    }

    /**
     * @param array<string, string> $registration
     */
    public function create(#[\SensitiveParameter] array $registration): User
    {
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $registration['password']
        );

        $user->setEmail($registration['username'])
            ->setPassword($hashedPassword)
            ->setFirstname($registration['firstname'])
            ->setSurname($registration['surname'])
        ;

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function remove(int $id): void
    {
        $user = $this->userService->get($id);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function update(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function resetPassword(User $user, #[\SensitiveParameter] string $password): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $this->em->persist($user);
        $this->em->flush();
    }
}
