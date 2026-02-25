<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RevokePermission;

use App\Organization\Domain\Exception\PermissionNotFoundException;
use App\Organization\Domain\Repository\PermissionRepositoryInterface;
use App\Organization\Domain\ValueObject\PermissionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RevokePermissionHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(RevokePermissionCommand $command): void
    {
        $permission = $this->permissionRepository->findById(
            PermissionId::fromString($command->permissionId),
        );

        if (null === $permission) {
            throw PermissionNotFoundException::withId($command->permissionId);
        }

        $this->permissionRepository->delete($permission);
    }
}
