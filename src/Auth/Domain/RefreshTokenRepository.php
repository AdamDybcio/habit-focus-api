<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use Symfony\Component\Uid\Uuid;

interface RefreshTokenRepository
{
    public function save(RefreshToken $token): void;

    /**
     * Finds a token by the SHA-256 hash of its value.
     */
    public function findByHash(string $hash): ?RefreshToken;

    /**
     * Like {@see self::findByHash()}, but locks the row until the surrounding
     * transaction ends. Two concurrent refreshes of the same token are
     * therefore processed one after another, never both as "first use".
     *
     * Must be called inside a {@see \App\Shared\Application\Transaction}.
     */
    public function findByHashForUpdate(string $hash): ?RefreshToken;

    /**
     * Revokes every not yet revoked token of the family.
     */
    public function revokeFamily(Uuid $familyId, \DateTimeImmutable $now): void;

    /**
     * Deletes tokens that expired before the given moment.
     *
     * @return int number of deleted tokens
     */
    public function deleteExpiredBefore(\DateTimeImmutable $moment): int;
}
