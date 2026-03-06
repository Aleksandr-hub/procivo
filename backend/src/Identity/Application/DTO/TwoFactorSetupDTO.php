<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'TOTP two-factor authentication setup data')]
final readonly class TwoFactorSetupDTO implements \JsonSerializable
{
    /**
     * @param list<string> $backupCodes
     */
    public function __construct(
        #[OA\Property(description: 'QR code as SVG markup for authenticator apps')]
        public string $qrCodeSvg,
        #[OA\Property(description: 'TOTP secret key for manual entry')]
        public string $secret,
        #[OA\Property(description: 'One-time backup codes', type: 'array', items: new OA\Items(type: 'string'))]
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
