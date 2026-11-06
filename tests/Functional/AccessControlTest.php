<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class AccessControlTest extends ApiTestCase
{
    public function testProtectedEndpointRequiresAToken(): void
    {
        $this->getJson('/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGarbageTokenIsRejected(): void
    {
        $this->getJson('/me', 'not.a.jwt');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRefreshTokenCannotBeUsedAsAccessToken(): void
    {
        $tokens = $this->register();

        $this->getJson('/me', $tokens['refreshToken']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testHealthIsPublic(): void
    {
        $body = $this->getJson('/health');

        self::assertResponseIsSuccessful();
        self::assertSame(['status' => 'ok'], $body);
    }

    public function testResponsesCarrySecurityHeaders(): void
    {
        $this->getJson('/health');

        $headers = $this->client->getResponse()->headers;
        self::assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $headers->get('X-Frame-Options'));
        self::assertStringContainsString('no-store', (string) $headers->get('Cache-Control'));
    }
}
