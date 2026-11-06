<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

/**
 * The refresh token is unknown, expired, revoked or was already used.
 */
final class InvalidRefreshToken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid refresh token.');
    }
}
