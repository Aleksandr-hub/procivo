<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\ValueObject;

enum TaskStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';
    case Blocked = 'blocked';
    case Cancelled = 'cancelled';
}
