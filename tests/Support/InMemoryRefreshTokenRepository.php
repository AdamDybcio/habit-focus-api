<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryRefreshTokenRepository implements RefreshTokenRepository
{
    /** @var array<string, RefreshToken> keyed by token hash */
    private array $tokens = [];

    public function save(RefreshToken $token): void
    {
        $this->tokens[$token->tokenHash()] = $token;
    }

    public function findByHash(string $hash): ?RefreshToken
    {
        return $this->tokens[$hash] ?? null;
    }

    public function findByHashForUpdate(string $hash): ?RefreshToken
    {
        return $this->findByHash($hash);
    }

    public function revokeFamily(Uuid $familyId, \DateTimeImmutable $now): void
    {
        foreach ($this->tokens as $token) {
            if ($token->familyId()->equals($familyId)) {
                $token->revoke($now);
            }
        }
    }

    public function deleteExpiredBefore(\DateTimeImmutable $moment): int
    {
        $deleted = 0;
        foreach ($this->tokens as $hash => $token) {
            if ($token->expiresAt() < $moment) {
                unset($this->tokens[$hash]);
                ++$deleted;
            }
        }

        return $deleted;
    }

    public function count(): int
    {
        return \count($this->tokens);
    }

    /**
     * @return list<RefreshToken>
     */
    public function all(): array
    {
        return array_values($this->tokens);
    }
}
