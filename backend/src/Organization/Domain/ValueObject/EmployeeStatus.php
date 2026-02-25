<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Dismissed = 'dismissed';
}
