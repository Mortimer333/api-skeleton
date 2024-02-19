<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserService
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Security $security,
    ) {
    }

    public function get(int $id): User
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            throw new \Exception('Cannot find selected user', 400);
        }

        return $user;
    }

    public function getLoggedInUser(): User
    {
        /** @var ?User $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception('User is not logged in', 400);
        }

        return $user;
    }

    public function isLoggedIn(): bool
    {
        return (bool) $this->security->getUser();
    }

    /**
     * @return array{
     *     id: int|null,
     *     email: string|null,
     *     firstname: string|null,
     *     surname: string|null,
     *     roles?: array<string>,
     * }
     */
    public function serialize(User $user, bool $roles = false): array
    {
        $details = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'surname' => $user->getSurname(),
        ];

        if ($roles) {
            $details['roles'] = $user->getRoles();
        }

        return $details;
    }
}
