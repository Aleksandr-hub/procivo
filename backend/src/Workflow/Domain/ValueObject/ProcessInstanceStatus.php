<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

enum ProcessInstanceStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Error = 'error';
}
