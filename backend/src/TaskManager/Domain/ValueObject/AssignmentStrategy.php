<?php

declare(strict_types=1);

namespace App\TaskManager\Domain\ValueObject;

enum AssignmentStrategy: string
{
    case Unassigned = 'unassigned';
    case SpecificUser = 'specific_user';
    case ByRole = 'by_role';
    case ByDepartment = 'by_department';
    case FromVariable = 'from_variable';
}
