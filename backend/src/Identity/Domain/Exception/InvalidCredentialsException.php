<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Invalid email or password.');
    }
}
