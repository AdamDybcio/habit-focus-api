<?php

declare(strict_types=1);

namespace App\Auth\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * One refresh token of a login session.
 *
 * Only the SHA-256 hash of the token is stored, so a database leak does not
 * reveal usable tokens. Tokens are single-use: each refresh marks the token as
 * used and issues a new one in the same family. Presenting a used token again
 * means it was stolen, and the whole family gets revoked.
 */
#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(name: 'idx_refresh_tokens_family', columns: ['family_id'])]
#[ORM\Index(name: 'idx_refresh_tokens_expires_at', columns: ['expires_at'])]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(name: 'family_id', type: UuidType::NAME)]
    private Uuid $familyId;

    #[ORM\Column(name: 'user_id', type: UuidType::NAME)]
    private Uuid $userId;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'used_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(
        Uuid $id,
        Uuid $familyId,
        Uuid $userId,
        string $tokenHash,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
    ) {
        $this->id = $id;
        $this->familyId = $familyId;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function familyId(): Uuid
    {
        return $this->familyId;
    }

    public function userId(): Uuid
    {
        return $this->userId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    public function isUsed(): bool
    {
        return null !== $this->usedAt;
    }

    public function markUsed(\DateTimeImmutable $now): void
    {
        $this->usedAt = $now;
    }

    public function revoke(\DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }
}
