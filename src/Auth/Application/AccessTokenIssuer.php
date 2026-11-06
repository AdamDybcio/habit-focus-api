<?php

declare(strict_types=1);

namespace App\Auth\Application;

use App\User\Domain\User;

/**
 * Creates short-lived access tokens. The format (JWT) is an infrastructure
 * detail hidden behind this interface.
 */
interface AccessTokenIssuer
{
    public function issue(User $user): string;

    /**
     * Lifetime of issued access tokens, in seconds.
     */
    public function ttlSeconds(): int;
}
