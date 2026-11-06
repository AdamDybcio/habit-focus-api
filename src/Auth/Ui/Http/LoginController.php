<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http;

use App\Auth\Application\LoginHandler;
use App\Auth\Infrastructure\Http\AuthRateLimiter;
use App\Auth\Ui\Http\Request\LoginRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
final readonly class LoginController
{
    public function __construct(
        private LoginHandler $login,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request, #[MapRequestPayload] LoginRequest $payload): JsonResponse
    {
        $this->rateLimiter->checkLogin($request, $payload->email);

        return new JsonResponse(($this->login)($payload->email, $payload->password)->toArray());
    }
}
