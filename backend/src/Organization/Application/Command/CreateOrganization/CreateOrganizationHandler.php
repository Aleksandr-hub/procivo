<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateOrganization;

use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Exception\OrganizationSlugAlreadyExistsException;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use App\Organization\Domain\ValueObject\OrganizationName;
use App\Organization\Domain\ValueObject\OrganizationSlug;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    public function __invoke(CreateOrganizationCommand $command): void
    {
        $slug = new OrganizationSlug($command->slug);

        if ($this->organizationRepository->existsBySlug($slug)) {
            throw OrganizationSlugAlreadyExistsException::withSlug($command->slug);
        }

        $organization = Organization::create(
            id: OrganizationId::fromString($command->id),
            name: new OrganizationName($command->name),
            slug: $slug,
            description: $command->description,
            ownerUserId: $this->currentUserProvider->getUserId(),
        );

        $this->organizationRepository->save($organization);
    }
}
