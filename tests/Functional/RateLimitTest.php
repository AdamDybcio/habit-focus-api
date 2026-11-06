<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class RateLimitTest extends ApiTestCase
{
    public function testLoginIsThrottledPerEmail(): void
    {
        // Keep the kernel (and the in-memory limiter state) between requests.
        $this->client->disableReboot();

        for ($i = 0; $i < 5; ++$i) {
            $this->postJson('/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password']);
            self::assertResponseStatusCodeSame(401);
        }

        $body = $this->postJson('/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password']);

        self::assertResponseStatusCodeSame(429);
        self::assertSame(429, $body['status']);
        self::assertNotNull($this->client->getResponse()->headers->get('Retry-After'));
    }

    public function testThrottlingOneEmailDoesNotBlockAnother(): void
    {
        $this->client->disableReboot();

        for ($i = 0; $i < 6; ++$i) {
            $this->postJson('/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-password']);
        }

        $this->postJson('/auth/login', ['email' => 'grace@example.com', 'password' => 'wrong-password']);

        self::assertResponseStatusCodeSame(401);
    }
}
