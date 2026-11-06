<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application;

use App\Auth\Domain\Exception\InvalidRefreshToken;
use App\Tests\Support\AuthFixture;
use PHPUnit\Framework\TestCase;

final class LogoutHandlerTest extends TestCase
{
    public function testRevokesTheWholeSession(): void
    {
        $fixture = new AuthFixture();
        ($fixture->registerUser)('ada@example.com', 'correct horse');
        $first = ($fixture->login)('ada@example.com', 'correct horse');
        $rotated = ($fixture->refresh)($first->refreshToken);

        ($fixture->logout)($rotated->refreshToken);

        $this->expectException(InvalidRefreshToken::class);
        ($fixture->refresh)($rotated->refreshToken);
    }

    public function testLeavesOtherSessionsAlone(): void
    {
        $fixture = new AuthFixture();
        ($fixture->registerUser)('ada@example.com', 'correct horse');
        $phone = ($fixture->login)('ada@example.com', 'correct horse');
        $tablet = ($fixture->login)('ada@example.com', 'correct horse');

        ($fixture->logout)($phone->refreshToken);

        $pair = ($fixture->refresh)($tablet->refreshToken);
        self::assertNotSame('', $pair->refreshToken);
    }

    public function testUnknownTokenIsNotAnError(): void
    {
        $fixture = new AuthFixture();

        ($fixture->logout)('never-issued');

        $this->addToAssertionCount(1);
    }
}
