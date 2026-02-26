<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

enum TokenStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Waiting = 'waiting';
}
