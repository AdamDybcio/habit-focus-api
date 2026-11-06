<?php

declare(strict_types=1);

namespace App\User\Ui\Http;

use App\User\Domain\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Returns the profile of the authenticated user. Also a cheap way to check
 * that an access token works.
 */
#[AsController]
#[Route('/me', name: 'me', methods: ['GET'])]
final readonly class MeController
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->id()->toRfc4122(),
            'email' => $user->email(),
            'createdAt' => $user->createdAt()->format(\DATE_ATOM),
        ]);
    }
}
