<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\AddComment;

use App\Shared\Application\Command\CommandInterface;

final readonly class AddCommentCommand implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $taskId,
        public string $authorId,
        public string $body,
        public ?string $parentId = null,
    ) {
    }
}
