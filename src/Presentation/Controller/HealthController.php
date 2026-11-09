<?php

namespace App\Presentation\Controller;

use App\Application\Health\HealthChecker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function __invoke(HealthChecker $healthChecker): JsonResponse
    {
        // Redis down stays a 200: the site keeps serving from the database.
        return new JsonResponse(
            $healthChecker->check(),
            $healthChecker->isHealthy() ? 200 : 503,
        );
    }
}
