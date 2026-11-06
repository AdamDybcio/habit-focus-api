<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Shared\Application\Transaction;
use App\User\Application\RegisterUserHandler;
use App\User\Domain\Exception\EmailAlreadyRegistered;

/**
 * Registers an account and logs it in at once, so the client gets tokens
 * without a second request. The user and the first refresh token are saved
 * atomically.
 */
final readonly class RegisterAccountHandler
{
    public function __construct(
        private RegisterUserHandler $registerUser,
        private TokenPairIssuer $tokenPairIssuer,
        private Transaction $transaction,
    ) {
    }

    /**
     * @throws EmailAlreadyRegistered
     */
    public function __invoke(string $email, string $plainPassword): TokenPair
    {
        return $this->transaction->wrap(function () use ($email, $plainPassword): TokenPair {
            $user = ($this->registerUser)($email, $plainPassword);

            return $this->tokenPairIssuer->issue($user);
        });
    }
}
