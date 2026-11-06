<?php

declare(strict_types=1);

namespace App\Auth\Application;

/**
 * Access token (short-lived JWT) together with its refresh token.
 */
final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {
    }

    /**
     * @return array{accessToken: string, refreshToken: string, expiresIn: int}
     */
    public function toArray(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'expiresIn' => $this->expiresIn,
        ];
    }
}
