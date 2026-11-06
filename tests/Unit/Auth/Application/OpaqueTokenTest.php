<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Application;

use App\Auth\Application\OpaqueToken;
use PHPUnit\Framework\TestCase;

final class OpaqueTokenTest extends TestCase
{
    public function testGeneratesUrlSafeTokenWithFullEntropy(): void
    {
        $token = OpaqueToken::generate();

        self::assertSame(43, \strlen($token)); // 32 bytes in unpadded base64
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $token);
    }

    public function testGeneratesDifferentTokensEveryTime(): void
    {
        self::assertNotSame(OpaqueToken::generate(), OpaqueToken::generate());
    }

    public function testHashIsSha256AndDoesNotContainTheToken(): void
    {
        $hash = OpaqueToken::hash('secret');

        self::assertSame(hash('sha256', 'secret'), $hash);
        self::assertSame(64, \strlen($hash));
        self::assertStringNotContainsString('secret', $hash);
    }
}
