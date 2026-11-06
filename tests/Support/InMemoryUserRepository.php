<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\User\Domain\Exception\EmailAlreadyRegistered;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function findById(Uuid $id): ?User
    {
        return $this->users[$id->toRfc4122()] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email() === $email) {
                return $user;
            }
        }

        return null;
    }

    public function save(User $user): void
    {
        $existing = $this->findByEmail($user->email());
        if (null !== $existing && !$existing->id()->equals($user->id())) {
            throw new EmailAlreadyRegistered();
        }

        $this->users[$user->id()->toRfc4122()] = $user;
    }

    public function count(): int
    {
        return \count($this->users);
    }
}
