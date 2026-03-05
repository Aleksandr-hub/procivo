<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Query\GetDashboardStats\GetDashboardStatsQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/organizations/{organizationId}/dashboard', name: 'api_v1_dashboard_')]
final readonly class DashboardController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');
        $stats = $this->queryBus->ask(new GetDashboardStatsQuery($organizationId));

        return new JsonResponse($stats);
    }
}
