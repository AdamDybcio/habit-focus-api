<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\RefreshTokenRepository;
use Symfony\Component\Clock\ClockInterface;

/**
 * Ends a session by revoking all refresh tokens of its family.
 *
 * Idempotent: an unknown or already revoked token is not an error, so the
 * client can always clear its local session.
 */
final readonly class LogoutHandler
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $plainToken): void
    {
        $token = $this->refreshTokens->findByHash(OpaqueToken::hash($plainToken));
        if (null === $token) {
            return;
        }

        $this->refreshTokens->revokeFamily($token->familyId(), $this->clock->now());
    }
}
