<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application;

use App\Auth\Application\OpaqueToken;
use App\Auth\Domain\Exception\InvalidCredentials;
use App\Tests\Support\AuthFixture;
use PHPUnit\Framework\TestCase;

final class LoginHandlerTest extends TestCase
{
    private AuthFixture $fixture;

    protected function setUp(): void
    {
        $this->fixture = new AuthFixture();
        ($this->fixture->registerUser)('ada@example.com', 'correct horse');
    }

    public function testReturnsTokenPairAndStoresOnlyTheHashOfTheRefreshToken(): void
    {
        $pair = ($this->fixture->login)('ada@example.com', 'correct horse');

        self::assertStringStartsWith('access-for-', $pair->accessToken);
        self::assertSame(900, $pair->expiresIn);

        $stored = $this->fixture->refreshTokens->findByHash(OpaqueToken::hash($pair->refreshToken));
        self::assertNotNull($stored);
        self::assertNotSame($pair->refreshToken, $stored->tokenHash());
    }

    public function testEmailIsCaseInsensitive(): void
    {
        $pair = ($this->fixture->login)('ADA@Example.com', 'correct horse');

        self::assertNotSame('', $pair->refreshToken);
    }

    public function testRejectsWrongPassword(): void
    {
        $this->expectException(InvalidCredentials::class);

        ($this->fixture->login)('ada@example.com', 'wrong password');
    }

    public function testRejectsUnknownEmailWithTheSameException(): void
    {
        $this->expectException(InvalidCredentials::class);

        ($this->fixture->login)('nobody@example.com', 'correct horse');
    }

    public function testEveryLoginStartsANewSession(): void
    {
        $first = ($this->fixture->login)('ada@example.com', 'correct horse');
        $second = ($this->fixture->login)('ada@example.com', 'correct horse');

        $firstToken = $this->fixture->refreshTokens->findByHash(OpaqueToken::hash($first->refreshToken));
        $secondToken = $this->fixture->refreshTokens->findByHash(OpaqueToken::hash($second->refreshToken));

        self::assertNotNull($firstToken);
        self::assertNotNull($secondToken);
        self::assertFalse($firstToken->familyId()->equals($secondToken->familyId()));
    }

    public function testRehashesPasswordWhenHasherAsksForIt(): void
    {
        $this->fixture->hasher->needsRehash = true;

        ($this->fixture->login)('ada@example.com', 'correct horse');

        $user = $this->fixture->users->findByEmail('ada@example.com');
        self::assertNotNull($user);
        self::assertSame('hashed:correct horse', $user->getPassword());
    }
}
