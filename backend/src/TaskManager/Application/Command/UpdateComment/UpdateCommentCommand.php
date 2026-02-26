<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\UpdateComment;

use App\Shared\Application\Command\CommandInterface;

final readonly class UpdateCommentCommand implements CommandInterface
{
    public function __construct(
        public string $commentId,
        public string $body,
    ) {
    }
}
