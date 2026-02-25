<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
