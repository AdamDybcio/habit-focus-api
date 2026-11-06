<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Jwt;

use App\Auth\Application\AccessTokenIssuer;
use App\User\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Issues RS256-signed JWTs with LexikJWTAuthenticationBundle.
 */
final readonly class LexikAccessTokenIssuer implements AccessTokenIssuer
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        #[Autowire('%app.access_token_ttl%')]
        private int $ttlSeconds,
    ) {
    }

    public function issue(User $user): string
    {
        return $this->jwtManager->create($user);
    }

    public function ttlSeconds(): int
    {
        return $this->ttlSeconds;
    }
}
