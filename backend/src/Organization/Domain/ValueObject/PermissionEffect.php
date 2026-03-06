<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObject;

/**
 * Effect of a user permission override — allow or deny.
 */
enum PermissionEffect: string
{
    /** Explicitly allow — overrides role/department permissions */
    case Allow = 'allow';

    /** Explicitly deny — blocks access regardless of other permissions */
    case Deny = 'deny';
}
