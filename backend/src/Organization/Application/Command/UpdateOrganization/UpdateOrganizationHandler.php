<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UpdateOrganization;

use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(UpdateOrganizationCommand $command): void
    {
        $organization = $this->organizationRepository->findById(
            OrganizationId::fromString($command->organizationId),
        );

        if (null === $organization) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        $organization->update(
            new OrganizationName($command->name),
            $command->description,
        );

        $this->organizationRepository->save($organization);
    }
}
