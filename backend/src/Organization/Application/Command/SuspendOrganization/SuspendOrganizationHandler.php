<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SuspendOrganization;

use App\Organization\Domain\Exception\OrganizationNotFoundException;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SuspendOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {
    }

    public function __invoke(SuspendOrganizationCommand $command): void
    {
        $organization = $this->organizationRepository->findById(
            OrganizationId::fromString($command->organizationId),
        );

        if (null === $organization) {
            throw OrganizationNotFoundException::withId($command->organizationId);
        }

        $organization->suspend();

        $this->organizationRepository->save($organization);
    }
}
