<?php

declare(strict_types=1);

namespace App\TaskManager\Presentation\Controller;

use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\QueryBusInterface;
use App\TaskManager\Application\Query\GetDashboardStats\GetDashboardStatsQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Dashboard')]
#[Route('/api/v1/organizations/{organizationId}/dashboard', name: 'api_v1_dashboard_')]
final readonly class DashboardController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Get(summary: 'Get dashboard statistics')]
    #[OA\Response(
        response: 200,
        description: 'Dashboard stats',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'tasks_by_status', type: 'object', description: 'Task counts grouped by status', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
            new OA\Property(property: 'tasks_completed_by_day', type: 'array', description: 'Tasks completed per day (last 30 days)', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'day', type: 'string', format: 'date'),
                    new OA\Property(property: 'cnt', type: 'integer'),
                ],
                type: 'object',
            )),
            new OA\Property(property: 'processes_by_status', type: 'object', description: 'Process instance counts grouped by status', additionalProperties: new OA\AdditionalProperties(type: 'integer')),
        ]),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(string $organizationId): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'TASK_VIEW');
        $stats = $this->queryBus->ask(new GetDashboardStatsQuery($organizationId));

        return new JsonResponse($stats);
    }
}
