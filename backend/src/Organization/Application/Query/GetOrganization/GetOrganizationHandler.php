<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\GetOrganization;

use App\Organization\Application\DTO\OrganizationDTO;
use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(GetOrganizationQuery $query): OrganizationDTO
    {
        $organization = $this->organizationRepository->findById(
            OrganizationId::fromString($query->organizationId),
        );

        if (null === $organization) {
            throw OrganizationNotFoundException::withId($query->organizationId);
        }

        return OrganizationDTO::fromEntity($organization);
    }
}
