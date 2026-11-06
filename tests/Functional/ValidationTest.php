<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

final class ValidationTest extends ApiTestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidRegistrations(): iterable
    {
        yield 'not an email' => [['email' => 'nope', 'password' => 'correct horse battery']];
        yield 'password too short' => [['email' => 'ada@example.com', 'password' => 'short']];
        yield 'password too long' => [['email' => 'ada@example.com', 'password' => str_repeat('a', 129)]];
        yield 'missing fields' => [[]];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('invalidRegistrations')]
    public function testRegisterRejectsInvalidInput(array $body): void
    {
        $response = $this->postJson('/auth/register', $body);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('violations', $response);
    }

    public function testErrorsUseProblemJson(): void
    {
        $this->postJson('/auth/register', []);

        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testMalformedJsonIsABadRequest(): void
    {
        $this->client->request('POST', '/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: '{broken');

        self::assertResponseStatusCodeSame(400);
    }

    public function testUnknownRouteIsNotFoundAsProblemJson(): void
    {
        $this->postJson('/does-not-exist', []);

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    public function testWrongMethodIsRejected(): void
    {
        $this->client->jsonRequest('GET', '/auth/login');

        self::assertResponseStatusCodeSame(405);
    }
}
