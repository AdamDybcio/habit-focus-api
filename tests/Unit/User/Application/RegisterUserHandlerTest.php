<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application;

use App\Tests\Support\AuthFixture;
use App\User\Domain\Exception\EmailAlreadyRegistered;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    public function testCreatesUserWithHashedPassword(): void
    {
        $fixture = new AuthFixture();

        $user = ($fixture->registerUser)('Ada@Example.com', 'correct horse');

        self::assertSame('ada@example.com', $user->email());
        self::assertSame('hashed:correct horse', $user->getPassword());
        self::assertNotSame('correct horse', $user->getPassword());
        self::assertEquals($fixture->clock->now(), $user->createdAt());
        self::assertSame(1, $fixture->users->count());
    }

    public function testRejectsDuplicateEmailRegardlessOfCase(): void
    {
        $fixture = new AuthFixture();
        ($fixture->registerUser)('ada@example.com', 'correct horse');

        $this->expectException(EmailAlreadyRegistered::class);

        ($fixture->registerUser)('ADA@example.com', 'another password');
    }
}
