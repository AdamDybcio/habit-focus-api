<?php

declare(strict_types=1);

namespace App\Auth\Ui\Http;

use App\Auth\Application\RegisterAccountHandler;
use App\Auth\Infrastructure\Http\AuthRateLimiter;
use App\Auth\Ui\Http\Request\RegisterRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
final readonly class RegisterController
{
    public function __construct(
        private RegisterAccountHandler $registerAccount,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    public function __invoke(Request $request, #[MapRequestPayload] RegisterRequest $payload): JsonResponse
    {
        $this->rateLimiter->checkRegister($request);

        $pair = ($this->registerAccount)($payload->email, $payload->password);

        return new JsonResponse($pair->toArray(), Response::HTTP_CREATED);
    }
}
