<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application;

use App\Tests\Support\AuthFixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TokenPairIssuerTest extends TestCase
{
    public function testRefreshTokenExpiresAfterConfiguredTtl(): void
    {
        $fixture = new AuthFixture();
        $user = ($fixture->registerUser)('ada@example.com', 'correct horse');

        $fixture->issuer->issue($user);

        $stored = $fixture->refreshTokens->all()[0];
        $expected = $fixture->clock->now()->modify(\sprintf('+%d seconds', AuthFixture::REFRESH_TTL));
        self::assertEquals($expected, $stored->expiresAt());
    }

    public function testUsesGivenFamilyOrStartsANewOne(): void
    {
        $fixture = new AuthFixture();
        $user = ($fixture->registerUser)('ada@example.com', 'correct horse');
        $family = Uuid::v7();

        $fixture->issuer->issue($user, $family);
        $fixture->issuer->issue($user);

        [$first, $second] = $fixture->refreshTokens->all();
        self::assertTrue($first->familyId()->equals($family));
        self::assertFalse($second->familyId()->equals($family));
    }

    public function testPairToArrayMatchesApiContract(): void
    {
        $fixture = new AuthFixture();
        $user = ($fixture->registerUser)('ada@example.com', 'correct horse');

        $pair = $fixture->issuer->issue($user);
        $array = $pair->toArray();

        self::assertSame(
            [
                'accessToken' => $pair->accessToken,
                'refreshToken' => $pair->refreshToken,
                'expiresIn' => $pair->expiresIn,
            ],
            $array,
        );
    }
}
