<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class UserNotActiveException extends DomainException
{
    public static function withId(string $userId): self
    {
        return new self(\sprintf('User "%s" is not active.', $userId));
    }
}
