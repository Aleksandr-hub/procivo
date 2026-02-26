<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Command\DeleteComment;

use App\Shared\Application\Command\CommandInterface;

final readonly class DeleteCommentCommand implements CommandInterface
{
    public function __construct(
        public string $commentId,
    ) {
    }
}
