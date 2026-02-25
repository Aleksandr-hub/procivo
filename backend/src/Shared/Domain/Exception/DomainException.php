<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

class DomainException extends \DomainException
{
    protected int $statusCode = 400;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
