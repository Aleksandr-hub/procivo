<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListOrganizations;

use App\Organization\Application\DTO\OrganizationDTO;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListOrganizationsHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    /**
     * @return list<OrganizationDTO>
     */
    public function __invoke(ListOrganizationsQuery $query): array
    {
        $organizations = $this->organizationRepository->findByMemberUserId($query->userId);

        return array_map(
            static fn ($org) => OrganizationDTO::fromEntity($org),
            $organizations,
        );
    }
}
