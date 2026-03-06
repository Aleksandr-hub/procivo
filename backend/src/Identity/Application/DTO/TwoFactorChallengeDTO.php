<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class TwoFactorChallengeDTO implements \JsonSerializable
{
    public function __construct(
        public string $partialToken,
        public bool $twoFactorRequired = true,
    ) {
    }

    /**
     * @return array{partial_token: string, two_factor_required: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'partial_token' => $this->partialToken,
            'two_factor_required' => $this->twoFactorRequired,
        ];
    }
}
