<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class UserAlreadyExistsException extends DomainException
{
    public static function withEmail(string $email): self
    {
        return new self(\sprintf('User with email "%s" already exists.', $email));
    }
}
