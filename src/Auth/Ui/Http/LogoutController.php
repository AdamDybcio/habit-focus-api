<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http;

use App\Auth\Application\LogoutHandler;
use App\Auth\Infrastructure\Http\AuthRateLimiter;
use App\Auth\Ui\Http\Request\RefreshTokenRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/auth/logout', name: 'auth_logout', methods: ['POST'])]
final readonly class LogoutController
{
    public function __construct(
        private LogoutHandler $logout,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request, #[MapRequestPayload] RefreshTokenRequest $payload): Response
    {
        $this->rateLimiter->checkRefresh($request);

        ($this->logout)($payload->refreshToken);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
