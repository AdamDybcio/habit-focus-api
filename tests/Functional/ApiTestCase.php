<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class for HTTP tests that run against the real Postgres test database.
 * Tables are emptied before every test, so tests are independent.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected const PASSWORD = 'correct horse battery';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var Connection $connection */
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('TRUNCATE TABLE refresh_tokens, users RESTART IDENTITY CASCADE');
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     *
     * @return array<string, mixed> decoded JSON body (empty for an empty response)
     */
    protected function postJson(string $uri, array $body, array $headers = []): array
    {
        $this->client->jsonRequest('POST', $uri, $body, $headers);

        return $this->decode();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJson(string $uri, ?string $accessToken = null): array
    {
        $headers = null === $accessToken ? [] : ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken];
        $this->client->jsonRequest('GET', $uri, [], $headers);

        return $this->decode();
    }

    /**
     * @return array{accessToken: string, refreshToken: string, expiresIn: int}
     */
    protected function register(string $email = 'ada@example.com'): array
    {
        $body = $this->postJson('/auth/register', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseStatusCodeSame(201);

        /** @var array{accessToken: string, refreshToken: string, expiresIn: int} $body */
        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(): array
    {
        $content = $this->client->getResponse()->getContent();
        if (false === $content || '' === $content) {
            return [];
        }

        $decoded = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);

        return \is_array($decoded) ? $decoded : [];
    }
}
