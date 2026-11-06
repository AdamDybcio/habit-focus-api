<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Liveness probe for load balancers and deployment checks. It does not touch
 * the database on purpose, so it stays cheap and cannot leak internals.
 */
#[AsController]
#[Route('/health', name: 'health', methods: ['GET'])]
final class HealthController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
