<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

final readonly class TwoFactorSetupDTO implements \JsonSerializable
{
    /**
     * @param list<string> $backupCodes
     */
    public function __construct(
        public string $qrCodeSvg,
        public string $secret,
        public array $backupCodes,
    ) {
    }

    /**
     * @return array{qr_code_svg: string, secret: string, backup_codes: list<string>}
     */
    public function jsonSerialize(): array
    {
        return [
            'qr_code_svg' => $this->qrCodeSvg,
            'secret' => $this->secret,
            'backup_codes' => $this->backupCodes,
        ];
    }
}
