<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class EmailAlreadyRegistered extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Email is already registered.');
    }
}
