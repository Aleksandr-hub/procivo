<?php

declare(strict_types=1);

namespace App\Workflow\Application\Command\SetProcessDefinitionAccess;

use App\Shared\Application\Command\CommandInterface;

final readonly class SetProcessDefinitionAccessCommand implements CommandInterface
{
    /**
     * @param list<array{departmentId: ?string, roleId: ?string}> $entries
     */
    public function __construct(
        public string $processDefinitionId,
        public string $organizationId,
        public string $accessType,
        public array $entries,
        public string $actorId,
    ) {
    }
}
