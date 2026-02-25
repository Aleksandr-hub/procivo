<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Application\Command;

use App\Organization\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Organization\Application\Command\CreateOrganization\CreateOrganizationHandler;
use App\Organization\Application\Port\CurrentUserProviderInterface;
use App\Organization\Domain\Exception\OrganizationSlugAlreadyExistsException;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\ValueObject\OrganizationId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreateOrganizationHandlerTest extends TestCase
{
    #[Test]
    public function itCreatesAnOrganization(): void
    {
        $repository = $this->createMock(OrganizationRepositoryInterface::class);
        $userProvider = $this->createMock(CurrentUserProviderInterface::class);

        $repository->method('existsBySlug')->willReturn(false);
        $repository->expects(self::once())->method('save');
        $userProvider->method('getUserId')->willReturn('owner-user-id');

        $handler = new CreateOrganizationHandler($repository, $userProvider);

        $handler(new CreateOrganizationCommand(
            id: OrganizationId::generate()->value(),
            name: 'Acme Corp',
            slug: 'acme-corp',
            description: 'Test organization',
        ));
    }

    #[Test]
    public function itThrowsWhenSlugAlreadyExists(): void
    {
        $repository = $this->createMock(OrganizationRepositoryInterface::class);
        $userProvider = $this->createMock(CurrentUserProviderInterface::class);

        $repository->method('existsBySlug')->willReturn(true);

        $handler = new CreateOrganizationHandler($repository, $userProvider);

        $this->expectException(OrganizationSlugAlreadyExistsException::class);

        $handler(new CreateOrganizationCommand(
            id: OrganizationId::generate()->value(),
            name: 'Acme Corp',
            slug: 'acme-corp',
            description: null,
        ));
    }
}
