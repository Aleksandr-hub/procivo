<?php

declare(strict_types=1);

namespace App\Audit\Presentation\Controller;

use App\Audit\Application\Query\ListAuditLog\ListAuditLogQuery;
use App\Organization\Presentation\Security\OrganizationAuthorizer;
use App\Shared\Application\Bus\QueryBusInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Audit')]
#[Route('/api/v1/organizations/{organizationId}/audit-log', name: 'api_v1_audit_log_')]
final readonly class AuditLogController
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private OrganizationAuthorizer $authorizer,
    ) {
    }

    #[OA\Get(
        summary: 'List audit log entries',
        parameters: [
            new OA\Parameter(name: 'entity_type', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by entity type (e.g. task, process)'),
            new OA\Parameter(name: 'entity_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'actor_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50, maximum: 100)),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated audit log',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: \App\Audit\Application\DTO\AuditLogDTO::class))),
            new OA\Property(property: 'total', type: 'integer'),
        ]),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $organizationId, Request $request): JsonResponse
    {
        $this->authorizer->authorize($organizationId, 'AUDIT_VIEW');

        $entityType = $request->query->get('entity_type');
        $entityId = $request->query->get('entity_id');
        $actorId = $request->query->get('actor_id');
        $dateFromStr = $request->query->get('date_from');
        $dateToStr = $request->query->get('date_to');
        $page = max(1, (int) ($request->query->get('page', '1')));
        $limit = min(100, max(1, (int) ($request->query->get('limit', '50'))));

        $dateFrom = null;
        if (\is_string($dateFromStr) && '' !== $dateFromStr) {
            $dateFrom = new \DateTimeImmutable($dateFromStr);
        }

        $dateTo = null;
        if (\is_string($dateToStr) && '' !== $dateToStr) {
            $dateTo = new \DateTimeImmutable($dateToStr);
        }

        $result = $this->queryBus->ask(new ListAuditLogQuery(
            organizationId: $organizationId,
            entityType: \is_string($entityType) && '' !== $entityType ? $entityType : null,
            entityId: \is_string($entityId) && '' !== $entityId ? $entityId : null,
            actorId: \is_string($actorId) && '' !== $actorId ? $actorId : null,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            page: $page,
            limit: $limit,
        ));

        return new JsonResponse($result);
    }
}
