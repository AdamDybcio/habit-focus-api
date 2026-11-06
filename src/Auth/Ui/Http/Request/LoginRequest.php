<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Login does not enforce the password policy, only sane size limits: old
 * accounts must still be able to log in, and huge inputs are rejected before
 * they reach the (expensive) password hasher.
 */
final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(max: 128)]
        public string $password = '',
    ) {
    }
}
