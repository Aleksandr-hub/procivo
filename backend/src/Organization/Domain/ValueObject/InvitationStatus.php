<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
