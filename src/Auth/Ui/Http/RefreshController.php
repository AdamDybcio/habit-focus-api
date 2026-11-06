<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http;

use App\Auth\Application\RefreshHandler;
use App\Auth\Infrastructure\Http\AuthRateLimiter;
use App\Auth\Ui\Http\Request\RefreshTokenRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
final readonly class RefreshController
{
    public function __construct(
        private RefreshHandler $refresh,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request, #[MapRequestPayload] RefreshTokenRequest $payload): JsonResponse
    {
        $this->rateLimiter->checkRefresh($request);

        return new JsonResponse(($this->refresh)($payload->refreshToken)->toArray());
    }
}
