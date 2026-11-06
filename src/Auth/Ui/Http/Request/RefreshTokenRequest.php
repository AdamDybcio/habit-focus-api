<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of the refresh and logout endpoints.
 */
final readonly class RefreshTokenRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 512)]
        public string $refreshToken = '',
    ) {
    }
}
