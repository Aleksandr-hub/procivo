<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

enum NodeType: string
{
    case Start = 'start';
    case End = 'end';
    case Task = 'task';
    case ExclusiveGateway = 'exclusive_gateway';
    case ParallelGateway = 'parallel_gateway';
    case InclusiveGateway = 'inclusive_gateway';
    case Timer = 'timer';
    case SubProcess = 'sub_process';
    case Webhook = 'webhook';
    case Notification = 'notification';
}
