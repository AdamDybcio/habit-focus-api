<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * The whole token life cycle over HTTP, exactly as the mobile app uses it.
 */
final class AuthFlowTest extends ApiTestCase
{
    public function testRegisterReturnsTokensThatWorkOnProtectedEndpoints(): void
    {
        $tokens = $this->register();

        self::assertSame(900, $tokens['expiresIn']);

        $me = $this->getJson('/me', $tokens['accessToken']);
        self::assertResponseIsSuccessful();
        self::assertSame('ada@example.com', $me['email']);
        self::assertArrayNotHasKey('password', $me);
        self::assertArrayNotHasKey('passwordHash', $me);
    }

    public function testLoginAcceptsEmailInAnyCase(): void
    {
        $this->register('ada@example.com');

        $tokens = $this->postJson('/auth/login', ['email' => 'ADA@Example.com', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(200);
        self::assertArrayHasKey('accessToken', $tokens);
        self::assertArrayHasKey('refreshToken', $tokens);
    }

    public function testLoginFailsWithTheSameErrorForWrongPasswordAndUnknownEmail(): void
    {
        $this->register();

        $wrongPassword = $this->postJson('/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password']);
        self::assertResponseStatusCodeSame(401);

        $unknownEmail = $this->postJson('/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
        self::assertResponseStatusCodeSame(401);

        self::assertSame($wrongPassword, $unknownEmail);
    }

    public function testRefreshRotatesTokens(): void
    {
        $first = $this->register();

        $second = $this->postJson('/auth/refresh', ['refreshToken' => $first['refreshToken']]);

        self::assertResponseStatusCodeSame(200);
        self::assertNotSame($first['refreshToken'], $second['refreshToken']);

        $this->getJson('/me', (string) $second['accessToken']);
        self::assertResponseIsSuccessful();
    }

    public function testReusedRefreshTokenRevokesTheSession(): void
    {
        $first = $this->register();
        $second = $this->postJson('/auth/refresh', ['refreshToken' => $first['refreshToken']]);

        // An attacker (or a buggy client) replays the first token.
        $this->postJson('/auth/refresh', ['refreshToken' => $first['refreshToken']]);
        self::assertResponseStatusCodeSame(401);

        // The newest token was valid a moment ago, but the whole session is dead now.
        $this->postJson('/auth/refresh', ['refreshToken' => $second['refreshToken']]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testUnknownRefreshTokenIsRejected(): void
    {
        $this->postJson('/auth/refresh', ['refreshToken' => 'nope']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLogoutRevokesTheRefreshToken(): void
    {
        $tokens = $this->register();

        $this->postJson('/auth/logout', ['refreshToken' => $tokens['refreshToken']]);
        self::assertResponseStatusCodeSame(204);

        $this->postJson('/auth/refresh', ['refreshToken' => $tokens['refreshToken']]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testLogoutIsIdempotent(): void
    {
        $this->postJson('/auth/logout', ['refreshToken' => 'never-issued']);

        self::assertResponseStatusCodeSame(204);
    }

    public function testRegisteringTheSameEmailTwiceIsAConflict(): void
    {
        $this->register();

        $body = $this->postJson('/auth/register', ['email' => 'ADA@example.com', 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(409);
        self::assertSame(409, $body['status']);
    }
}
