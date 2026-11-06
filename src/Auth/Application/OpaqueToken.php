<?php

declare(strict_types=1);

namespace App\Auth\Application;

/**
 * Helpers for opaque refresh tokens: random strings with no meaning.
 */
final class OpaqueToken
{
    private const BYTES = 32;

    private function __construct()
    {
    }

    /**
     * Generates a token with 256 bits of entropy, URL-safe, 43 characters.
     */
    public static function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::BYTES)), '+/', '-_'), '=');
    }

    /**
     * A fast hash is enough here, the input is already random and long, so
     * there is nothing to brute-force (unlike passwords).
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
