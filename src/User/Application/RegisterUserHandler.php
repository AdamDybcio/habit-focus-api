<?php

declare(strict_types=1);

namespace App\User\Application;

use App\User\Domain\Exception\EmailAlreadyRegistered;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Creates a new account.
 */
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwordHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws EmailAlreadyRegistered
     */
    public function __invoke(string $email, string $plainPassword): User
    {
        $normalized = User::normalizeEmail($email);
        if (null !== $this->users->findByEmail($normalized)) {
            throw new EmailAlreadyRegistered();
        }

        $user = User::register(Uuid::v7(), $normalized, $this->clock->now());
        $user->changePasswordHash($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->users->save($user);

        return $user;
    }
}
