<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application;

use App\Tests\Support\AuthFixture;
use App\User\Domain\Exception\EmailAlreadyRegistered;
use PHPUnit\Framework\TestCase;

final class RegisterAccountHandlerTest extends TestCase
{
    public function testCreatesUserAndReturnsUsableTokens(): void
    {
        $fixture = new AuthFixture();

        $pair = ($fixture->registerAccount)('ada@example.com', 'correct horse');

        self::assertSame(1, $fixture->users->count());
        self::assertSame(1, $fixture->refreshTokens->count());
        self::assertNotSame('', ($fixture->refresh)($pair->refreshToken)->refreshToken);
    }

    public function testDuplicateEmailIssuesNoTokens(): void
    {
        $fixture = new AuthFixture();
        ($fixture->registerAccount)('ada@example.com', 'correct horse');

        try {
            ($fixture->registerAccount)('ada@example.com', 'correct horse');
            self::fail('Duplicate email must be rejected.');
        } catch (EmailAlreadyRegistered) {
        }

        self::assertSame(1, $fixture->refreshTokens->count());
    }
}
