<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetUserOverrides;

use App\Organization\Application\DTO\UserPermissionOverrideDTO;
use App\Organization\Domain\Repository\UserPermissionOverrideRepositoryInterface;
use App\Organization\Domain\ValueObject\EmployeeId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetUserOverridesHandler
{
    public function __construct(
        private UserPermissionOverrideRepositoryInterface $userOverrideRepository,
    ) {
    }

    /**
     * @return list<UserPermissionOverrideDTO>
     */
    public function __invoke(GetUserOverridesQuery $query): array
    {
        $overrides = $this->userOverrideRepository->findByEmployeeId(
            EmployeeId::fromString($query->employeeId),
        );

        return array_map(
            static fn ($o) => UserPermissionOverrideDTO::fromEntity($o),
            $overrides,
        );
    }
}
