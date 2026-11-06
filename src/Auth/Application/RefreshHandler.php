<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\Exception\InvalidRefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use App\Shared\Application\Transaction;
use App\User\Domain\UserRepository;
use Symfony\Component\Clock\ClockInterface;

/**
 * Exchanges a refresh token for a new pair (rotation).
 *
 * The old token becomes used. If an already used token shows up again, either
 * the legitimate client or a thief holds a stale copy. We cannot tell which,
 * so the whole session (token family) is revoked and the user must log in.
 */
final readonly class RefreshHandler
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private UserRepository $users,
        private TokenPairIssuer $tokenPairIssuer,
        private Transaction $transaction,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws InvalidRefreshToken
     */
    public function __invoke(string $plainToken): TokenPair
    {
        $hash = OpaqueToken::hash($plainToken);

        // The transaction returns the new pair or null when the token has to
        // be rejected. Throwing inside would roll back the family revocation,
        // so the exception is raised only after the commit.
        $pair = $this->transaction->wrap(function () use ($hash): ?TokenPair {
            $token = $this->refreshTokens->findByHashForUpdate($hash);
            if (null === $token) {
                return null;
            }

            $now = $this->clock->now();

            if ($token->isUsed()) {
                $this->refreshTokens->revokeFamily($token->familyId(), $now);

                return null;
            }

            if ($token->isRevoked() || $token->isExpired($now)) {
                return null;
            }

            $user = $this->users->findById($token->userId());
            if (null === $user) {
                return null;
            }

            $token->markUsed($now);

            return $this->tokenPairIssuer->issue($user, $token->familyId());
        });

        return $pair ?? throw new InvalidRefreshToken();
    }
}
