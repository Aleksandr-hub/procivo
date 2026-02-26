<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AddColumn;

use App\Shared\Application\Command\CommandInterface;

final readonly class AddColumnCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $boardId,
        public string $name,
        public ?string $statusMapping = null,
        public ?int $wipLimit = null,
        public ?string $color = null,
    ) {
    }
}
