<?php

declare(strict_types=1);

namespace App\Auth\Domain\Exception;

/**
 * Wrong email or password. The message is deliberately identical for an
 * unknown email and a wrong password, so it cannot be used to find accounts.
 */
final class InvalidCredentials extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
