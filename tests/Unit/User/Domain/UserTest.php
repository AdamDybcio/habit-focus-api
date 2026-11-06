<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain;

use App\User\Domain\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function emails(): iterable
    {
        yield 'lowercases' => ['Ada@Example.COM', 'ada@example.com'];
        yield 'trims' => ['  ada@example.com ', 'ada@example.com'];
        yield 'already normal' => ['ada@example.com', 'ada@example.com'];
    }

    #[DataProvider('emails')]
    public function testNormalizesEmail(string $input, string $expected): void
    {
        self::assertSame($expected, User::normalizeEmail($input));
    }

    public function testRegisterStoresNormalizedEmail(): void
    {
        $user = User::register(Uuid::v7(), 'Ada@Example.com', new \DateTimeImmutable());

        self::assertSame('ada@example.com', $user->email());
    }

    public function testIdentifierIsTheUuidNotTheEmail(): void
    {
        $id = Uuid::v7();
        $user = User::register($id, 'ada@example.com', new \DateTimeImmutable());

        self::assertSame($id->toRfc4122(), $user->getUserIdentifier());
    }

    public function testHasUserRoleOnly(): void
    {
        $user = User::register(Uuid::v7(), 'ada@example.com', new \DateTimeImmutable());

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
