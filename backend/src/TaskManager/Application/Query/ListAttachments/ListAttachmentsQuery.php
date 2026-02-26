<?php

declare(strict_types=1);

namespace App\TaskManager\Application\Query\ListAttachments;

use App\Shared\Application\Query\QueryInterface;

final readonly class ListAttachmentsQuery implements QueryInterface
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
