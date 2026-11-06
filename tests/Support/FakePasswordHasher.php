<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Not a real hash, only deterministic and instant. Never use outside tests.
 */
final class FakePasswordHasher implements UserPasswordHasherInterface
{
    public bool $needsRehash = false;

    public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function isPasswordValid(PasswordAuthenticatedUserInterface $user, string $plainPassword): bool
    {
        return 'hashed:'.$plainPassword === $user->getPassword();
    }

    public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
    {
        return $this->needsRehash;
    }
}
