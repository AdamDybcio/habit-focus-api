<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application;

use App\Auth\Application\OpaqueToken;
use App\Auth\Application\TokenPair;
use App\Auth\Domain\Exception\InvalidRefreshToken;
use App\Tests\Support\AuthFixture;
use PHPUnit\Framework\TestCase;

final class RefreshHandlerTest extends TestCase
{
    private AuthFixture $fixture;
    private TokenPair $initial;

    protected function setUp(): void
    {
        $this->fixture = new AuthFixture();
        ($this->fixture->registerUser)('ada@example.com', 'correct horse');
        $this->initial = ($this->fixture->login)('ada@example.com', 'correct horse');
    }

    public function testRotatesTheRefreshTokenInTheSameFamily(): void
    {
        $next = ($this->fixture->refresh)($this->initial->refreshToken);

        self::assertNotSame($this->initial->refreshToken, $next->refreshToken);

        $old = $this->fixture->refreshTokens->findByHash(OpaqueToken::hash($this->initial->refreshToken));
        $new = $this->fixture->refreshTokens->findByHash(OpaqueToken::hash($next->refreshToken));
        self::assertNotNull($old);
        self::assertNotNull($new);
        self::assertTrue($old->isUsed());
        self::assertFalse($new->isUsed());
        self::assertTrue($old->familyId()->equals($new->familyId()));
    }

    public function testChainOfRefreshesKeepsWorking(): void
    {
        $pair = $this->initial;
        for ($i = 0; $i < 3; ++$i) {
            $pair = ($this->fixture->refresh)($pair->refreshToken);
        }

        self::assertNotSame('', $pair->accessToken);
    }

    public function testReusingAUsedTokenRevokesTheWholeFamily(): void
    {
        $next = ($this->fixture->refresh)($this->initial->refreshToken);

        try {
            ($this->fixture->refresh)($this->initial->refreshToken); // replay of the old token
            self::fail('Reuse must be rejected.');
        } catch (InvalidRefreshToken) {
        }

        // The legitimate newest token is dead too: the session is over.
        $this->expectException(InvalidRefreshToken::class);
        ($this->fixture->refresh)($next->refreshToken);
    }

    public function testReuseDoesNotAffectOtherSessions(): void
    {
        $otherDevice = ($this->fixture->login)('ada@example.com', 'correct horse');
        ($this->fixture->refresh)($this->initial->refreshToken);

        try {
            ($this->fixture->refresh)($this->initial->refreshToken);
        } catch (InvalidRefreshToken) {
        }

        $pair = ($this->fixture->refresh)($otherDevice->refreshToken);
        self::assertNotSame('', $pair->refreshToken);
    }

    public function testRejectsUnknownToken(): void
    {
        $this->expectException(InvalidRefreshToken::class);

        ($this->fixture->refresh)('not-a-real-token');
    }

    public function testRejectsExpiredToken(): void
    {
        $this->fixture->clock->modify(\sprintf('+%d seconds', AuthFixture::REFRESH_TTL + 1));

        $this->expectException(InvalidRefreshToken::class);

        ($this->fixture->refresh)($this->initial->refreshToken);
    }

    public function testAcceptsTokenJustBeforeExpiry(): void
    {
        $this->fixture->clock->modify(\sprintf('+%d seconds', AuthFixture::REFRESH_TTL - 1));

        $pair = ($this->fixture->refresh)($this->initial->refreshToken);

        self::assertNotSame('', $pair->refreshToken);
    }

    public function testRejectsRevokedToken(): void
    {
        ($this->fixture->logout)($this->initial->refreshToken);

        $this->expectException(InvalidRefreshToken::class);

        ($this->fixture->refresh)($this->initial->refreshToken);
    }
}
