<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetEffectivePermissions;

use App\Organization\Application\DTO\EffectivePermissionDTO;
use App\Organization\Infrastructure\Security\Service\PermissionResolverInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetEffectivePermissionsHandler
{
    public function __construct(
        private PermissionResolverInterface $permissionResolver,
    ) {
    }

    /**
     * @return list<EffectivePermissionDTO>
     */
    public function __invoke(GetEffectivePermissionsQuery $query): array
    {
        $effective = $this->permissionResolver->resolveEffectivePermissions(
            $query->userId,
            $query->organizationId,
        );

        return array_map(
            static fn (array $entry) => new EffectivePermissionDTO(
                resource: $entry['resource'],
                action: $entry['action'],
                scope: $entry['scope'],
            ),
            $effective,
        );
    }
}
