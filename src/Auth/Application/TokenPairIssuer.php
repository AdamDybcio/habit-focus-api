<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use App\User\Domain\User;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

/**
 * Creates a new access + refresh token pair and stores the refresh token.
 */
final readonly class TokenPairIssuer
{
    public function __construct(
        private AccessTokenIssuer $accessTokens,
        private RefreshTokenRepository $refreshTokens,
        private ClockInterface $clock,
        #[Autowire('%app.refresh_token_ttl%')]
        private int $refreshTtlSeconds,
    ) {
    }

    /**
     * @param Uuid|null $familyId family of the session; null starts a new session
     */
    public function issue(User $user, ?Uuid $familyId = null): TokenPair
    {
        $now = $this->clock->now();
        $plain = OpaqueToken::generate();

        $this->refreshTokens->save(new RefreshToken(
            Uuid::v7(),
            $familyId ?? Uuid::v7(),
            $user->id(),
            OpaqueToken::hash($plain),
            $now,
            $now->modify(\sprintf('+%d seconds', $this->refreshTtlSeconds)),
        ));

        return new TokenPair(
            $this->accessTokens->issue($user),
            $plain,
            $this->accessTokens->ttlSeconds(),
        );
    }
}
