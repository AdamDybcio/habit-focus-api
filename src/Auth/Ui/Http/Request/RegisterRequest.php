<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 128)]
        public string $password = '',
    ) {
    }
}
