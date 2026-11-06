<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\Exception\InvalidCredentials;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Checks email and password and starts a new session.
 */
final class LoginHandler
{
    private ?User $placeholder = null;

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenPairIssuer $tokenPairIssuer,
    ) {
    }

    /**
     * @throws InvalidCredentials
     */
    public function __invoke(string $email, string $plainPassword): TokenPair
    {
        $user = $this->users->findByEmail(User::normalizeEmail($email));

        if (null === $user) {
            // Spend the same time as for a real user, so response time does
            // not reveal whether the email exists.
            $this->passwordHasher->isPasswordValid($this->placeholder(), $plainPassword);

            throw new InvalidCredentials();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $plainPassword)) {
            throw new InvalidCredentials();
        }

        if ($this->passwordHasher->needsRehash($user)) {
            // Hashing parameters were strengthened since the password was stored.
            $user->changePasswordHash($this->passwordHasher->hashPassword($user, $plainPassword));
            $this->users->save($user);
        }

        return $this->tokenPairIssuer->issue($user);
    }

    private function placeholder(): User
    {
        if (null === $this->placeholder) {
            $placeholder = User::register(Uuid::v7(), 'placeholder@invalid', new \DateTimeImmutable());
            $placeholder->changePasswordHash($this->passwordHasher->hashPassword($placeholder, 'placeholder'));
            $this->placeholder = $placeholder;
        }

        return $this->placeholder;
    }
}
