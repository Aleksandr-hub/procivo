<?php

declare(strict_types=1);

namespace App\Workflow\Domain\ValueObject;

enum AccessType: string
{
    case View = 'view';
    case Start = 'start';
}
