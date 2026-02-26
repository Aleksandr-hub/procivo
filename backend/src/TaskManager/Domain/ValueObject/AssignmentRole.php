<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\ValueObject;

enum AssignmentRole: string
{
    case Assignee = 'assignee';
    case Reviewer = 'reviewer';
    case Watcher = 'watcher';
}
