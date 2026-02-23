<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Blocked = 'blocked';
}
