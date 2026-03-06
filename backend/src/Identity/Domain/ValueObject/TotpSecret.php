<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

final readonly class TotpSecret
{
    public function __construct(
        private string $encryptedValue,
    ) {
    }

    public function value(): string
    {
        return $this->encryptedValue;
    }
}
