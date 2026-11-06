<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Auth\Application\LoginHandler;
use App\Auth\Application\LogoutHandler;
use App\Auth\Application\RefreshHandler;
use App\Auth\Application\RegisterAccountHandler;
use App\Auth\Application\TokenPairIssuer;
use App\User\Application\RegisterUserHandler;
use Symfony\Component\Clock\MockClock;

/**
 * Wires the application handlers with in-memory fakes for unit tests.
 */
final class AuthFixture
{
    public const REFRESH_TTL = 2592000;

    public readonly MockClock $clock;
    public readonly InMemoryUserRepository $users;
    public readonly InMemoryRefreshTokenRepository $refreshTokens;
    public readonly FakePasswordHasher $hasher;
    public readonly TokenPairIssuer $issuer;
    public readonly RegisterUserHandler $registerUser;
    public readonly RegisterAccountHandler $registerAccount;
    public readonly LoginHandler $login;
    public readonly RefreshHandler $refresh;
    public readonly LogoutHandler $logout;

    public function __construct()
    {
        $this->clock = new MockClock('2026-01-01 12:00:00');
        $this->users = new InMemoryUserRepository();
        $this->refreshTokens = new InMemoryRefreshTokenRepository();
        $this->hasher = new FakePasswordHasher();
        $transaction = new PassthroughTransaction();

        $this->issuer = new TokenPairIssuer(
            new FakeAccessTokenIssuer(),
            $this->refreshTokens,
            $this->clock,
            self::REFRESH_TTL,
        );
        $this->registerUser = new RegisterUserHandler($this->users, $this->hasher, $this->clock);
        $this->registerAccount = new RegisterAccountHandler($this->registerUser, $this->issuer, $transaction);
        $this->login = new LoginHandler($this->users, $this->hasher, $this->issuer);
        $this->refresh = new RefreshHandler(
            $this->refreshTokens,
            $this->users,
            $this->issuer,
            $transaction,
            $this->clock,
        );
        $this->logout = new LogoutHandler($this->refreshTokens, $this->clock);
    }
}
