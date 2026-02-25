<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

enum DepartmentStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
